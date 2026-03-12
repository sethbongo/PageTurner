@extends('layouts.main-navigation')


@section('navlinks')
<div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
    <x-nav-link :href="route('admin_home')" :active="request()->routeIs('admin_home')">
        {{ __('Dashboard') }}
    </x-nav-link>
    
    <x-nav-link href="{{ route('admin.manage_books') }}" :active="request()->routeIs('admin.manage_books')">
        {{ __('Manage Books') }}
    </x-nav-link>

    <x-nav-link href="{{ route('admin.manage_categories') }}" :active="request()->routeIs('admin.manage_categories')">
        {{ __('Manage Categories') }}
    </x-nav-link>

    <x-nav-link href="{{ route('admin.customer_orders') }}" :active="request()->routeIs('admin.customer_orders')">
        {{ __('Customer Orders') }}
    </x-nav-link>
</div>
@endsection

@section('responsive-navlinks')
    <x-responsive-nav-link :href="route('admin_home')" :active="request()->routeIs('admin_home')">
        {{ __('Dashboard') }}
    </x-responsive-nav-link>

    <x-responsive-nav-link :href="route('admin.manage_books')" :active="request()->routeIs('admin.manage_books')">
        {{ __('Manage Books') }}
    </x-responsive-nav-link>

    <x-responsive-nav-link :href="route('admin.manage_categories')" :active="request()->routeIs('admin.manage_categories')">
        {{ __('Manage Categories') }}
    </x-responsive-nav-link>

    <x-responsive-nav-link :href="route('admin.customer_orders')" :active="request()->routeIs('admin.customer_orders')">
        {{ __('Customer Orders') }}
    </x-responsive-nav-link>
@endsection
