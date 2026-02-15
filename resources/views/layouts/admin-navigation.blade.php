@extends('layouts.main-navigation')


@section('navlinks')
<div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
    <x-nav-link :href="route('admin_home')" :active="request()->routeIs('admin_home')">
        {{ __('Dashboard') }}
    </x-nav-link>
    
    <x-nav-link href="{{ route('admin.manage_books') }}" :active="request()->routeIs('admin.manage_books')">
        {{ __('Manage Books') }}
    </x-nav-link>

    <x-nav-link href="#" :active="false">
        {{ __('Manage Categories') }}
    </x-nav-link>

    <x-nav-link href="#" :active="false">
        {{ __('Customer Orders') }}
    </x-nav-link>
</div>
@endsection
