<?php

namespace App\Http\Livewire\Front\Checkout;

use App\Models\Front\Cart\CartSession;
use App\Models\Front\Catalog\Product;
use Livewire\Component;
use Livewire\WithPagination;

/**
 *
 */
class CartDrawer extends Component
{

    use WithPagination;
    /**
     * @var
     */
    protected $cart;

    /**
     * @var string[]
     */
    protected $listeners = ['addCartItem'];


    /**
     * @return void
     */
    public function mount() {
        $this->setCart();

        //dd($this->cart->get());
    }


    /**
     * @param Product $product
     * @param int     $quantity
     *
     * @return void
     */
    public function addCartItem(Product $product, int $quantity)
    {
        $this->setCart();

        $this->cart->add($product, $quantity);

        $this->updateNavIcon();
    }


    public function changeQuantity(int $id, int $quantity)
    {
        //dd($id, $quantity);
        $this->setCart();
        $product = Product::query()->find($id);

        $this->cart->add($product, $quantity);

        $this->updateNavIcon();

        return redirect()->to(request()->server('HTTP_REFERER'));
    }


    /**
     * @param int $product_id
     *
     * @return void
     */
    public function removeFromCart(int $product_id)
    {
        $this->setCart();

        $this->cart->remove($product_id);

        $this->updateNavIcon();

        return redirect()->to(request()->server('HTTP_REFERER'));
    }


    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function render()
    {
        return view('livewire.front.checkout.cart-drawer', ['cart' => $this->cart]);
    }


    /**
     * @return void
     */
    private function setCart()
    {
        $this->cart = CartSession::resolve();
    }


    private function updateNavIcon()
    {
        $this->emit('updateCartNavIcon', $this->cart->get()['count']);
    }
}
