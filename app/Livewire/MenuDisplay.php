<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Restaurant;
use Livewire\Component;
use Livewire\WithPagination;

class MenuDisplay extends Component
{
    use WithPagination;

    public Restaurant $restaurant;
    public $selectedCategory = null;
    public $search = '';
    public $cart = [];
    public $showCart = false;
    public $cartCount = 0;
    public $cartTotal = 0;

    protected $listeners = [
        'addToCart' => 'addToCart',
        'removeFromCart' => 'removeFromCart',
        'clearCart' => 'clearCart',
        'toggleCart' => 'toggleCart'
    ];

    protected $queryString = [
        'selectedCategory' => ['except' => null],
        'search' => ['except' => '']
    ];

    public function mount(Restaurant $restaurant)
    {
        $this->restaurant = $restaurant;
        $this->loadCartFromSession();
    }

    public function render()
    {
        $categories = Category::where('restaurant_id', $this->restaurant->id)
            ->where('is_active', true)
            ->withCount(['menuItems' => function ($query) {
                $query->available();
            }])
            ->having('menu_items_count', '>', 0)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $menuItemsQuery = MenuItem::where('restaurant_id', $this->restaurant->id)
            ->available()
            ->with(['category', 'reviews']);

        // Filtro por categoria
        if ($this->selectedCategory) {
            $menuItemsQuery->where('category_id', $this->selectedCategory);
        }

        // Filtro por busca
        if ($this->search) {
            $menuItemsQuery->where(function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%')
                    ->orWhereJsonContains('ingredients', $this->search)
                    ->orWhereJsonContains('tags', $this->search);
            });
        }

        $menuItems = $menuItemsQuery->ordered()->paginate(12);

        // Itens em destaque (se não há filtros)
        $featuredItems = collect();
        if (!$this->selectedCategory && !$this->search) {
            $featuredItems = MenuItem::where('restaurant_id', $this->restaurant->id)
                ->available()
                ->featured()
                ->with(['category', 'reviews'])
                ->ordered()
                ->limit(6)
                ->get();
        }

        return view('livewire.menu-display', [
            'categories' => $categories,
            'menuItems' => $menuItems,
            'featuredItems' => $featuredItems
        ]);
    }

    public function selectCategory($categoryId = null)
    {
        $this->selectedCategory = $categoryId;
        $this->resetPage();
        $this->dispatch('categoryChanged', $categoryId);
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function addToCart($itemId, $quantity = 1)
    {
        $menuItem = MenuItem::findOrFail($itemId);
        
        if (!$menuItem->is_available) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Este item não está disponível no momento.'
            ]);
            return;
        }

        $cartKey = $itemId;
        
        if (isset($this->cart[$cartKey])) {
            $this->cart[$cartKey]['quantity'] += $quantity;
        } else {
            $this->cart[$cartKey] = [
                'id' => $menuItem->id,
                'name' => $menuItem->name,
                'price' => $menuItem->effective_price,
                'quantity' => $quantity,
                'image' => $menuItem->image_url,
                'max_quantity' => 10 // Limite por item
            ];
        }

        // Limita quantidade máxima
        if ($this->cart[$cartKey]['quantity'] > $this->cart[$cartKey]['max_quantity']) {
            $this->cart[$cartKey]['quantity'] = $this->cart[$cartKey]['max_quantity'];
        }

        $this->updateCartTotals();
        $this->saveCartToSession();

        $this->dispatch('notification', [
            'type' => 'success',
            'message' => 'Item adicionado ao carrinho!'
        ]);

        $this->dispatch('cartUpdated', $this->cartCount);
    }

    public function removeFromCart($itemId)
    {
        if (isset($this->cart[$itemId])) {
            unset($this->cart[$itemId]);
            $this->updateCartTotals();
            $this->saveCartToSession();

            $this->dispatch('notification', [
                'type' => 'info',
                'message' => 'Item removido do carrinho.'
            ]);

            $this->dispatch('cartUpdated', $this->cartCount);
        }
    }

    public function updateCartItemQuantity($itemId, $quantity)
    {
        if ($quantity <= 0) {
            $this->removeFromCart($itemId);
            return;
        }

        if (isset($this->cart[$itemId])) {
            $maxQuantity = $this->cart[$itemId]['max_quantity'] ?? 10;
            $this->cart[$itemId]['quantity'] = min($quantity, $maxQuantity);
            
            $this->updateCartTotals();
            $this->saveCartToSession();
            
            $this->dispatch('cartUpdated', $this->cartCount);
        }
    }

    public function clearCart()
    {
        $this->cart = [];
        $this->updateCartTotals();
        $this->saveCartToSession();
        
        $this->dispatch('notification', [
            'type' => 'info',
            'message' => 'Carrinho limpo.'
        ]);

        $this->dispatch('cartUpdated', $this->cartCount);
    }

    public function toggleCart()
    {
        $this->showCart = !$this->showCart;
    }

    private function updateCartTotals()
    {
        $this->cartCount = array_sum(array_column($this->cart, 'quantity'));
        $this->cartTotal = array_sum(array_map(function ($item) {
            return $item['price'] * $item['quantity'];
        }, $this->cart));
    }

    private function saveCartToSession()
    {
        session()->put('cart_' . $this->restaurant->id, $this->cart);
    }

    private function loadCartFromSession()
    {
        $this->cart = session()->get('cart_' . $this->restaurant->id, []);
        $this->updateCartTotals();
    }

    public function proceedToCheckout()
    {
        if (empty($this->cart)) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Seu carrinho está vazio.'
            ]);
            return;
        }

        if ($this->cartTotal < $this->restaurant->minimum_order) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Valor mínimo do pedido: R$ ' . number_format($this->restaurant->minimum_order, 2, ',', '.')
            ]);
            return;
        }

        return redirect()->route('checkout', $this->restaurant->subdomain);
    }

    // Getters para uso no template
    public function getFormattedCartTotalProperty()
    {
        return 'R$ ' . number_format($this->cartTotal, 2, ',', '.');
    }

    public function getHasItemsProperty()
    {
        return !empty($this->cart);
    }
} 