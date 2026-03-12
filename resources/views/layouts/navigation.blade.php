@extends('layouts.main-navigation')

@section('navlinks')
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('customer.dashboard')" :active="request()->routeIs('customer.dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Browse Books') }}
                    </x-nav-link>
                    

                    <x-nav-link href="{{route('cart') }}" :active="request()->routeIs('cart')">
                        {{ __('Cart') }}
                    </x-nav-link>

                    
                    <x-nav-link href="{{route('orders.show') }}" :active="request()->routeIs('orders.show')">
                        {{ __('Orders') }}
                    </x-nav-link>

                    <x-nav-link href="{{ route('purchased-books.show') }}" :active="request()->routeIs('purchased-books.show')">
                        {{ __('Purchased books') }}
                    </x-nav-link>
                    
                </div>
@endsection

@section('responsive-navlinks')
    <x-responsive-nav-link :href="route('customer.dashboard')" :active="request()->routeIs('customer.dashboard')">
        {{ __('Dashboard') }}
    </x-responsive-nav-link>

    <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
        {{ __('Browse Books') }}
    </x-responsive-nav-link>

    <x-responsive-nav-link :href="route('cart')" :active="request()->routeIs('cart')">
        {{ __('Cart') }}
    </x-responsive-nav-link>

    <x-responsive-nav-link :href="route('orders.show')" :active="request()->routeIs('orders.show')">
        {{ __('Orders') }}
    </x-responsive-nav-link>

    <x-responsive-nav-link :href="route('purchased-books.show')" :active="request()->routeIs('purchased-books.show')">
        {{ __('Purchased books') }}
    </x-responsive-nav-link>
@endsection