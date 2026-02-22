              <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        <div class="flex items-center justify-between gap-4">
                            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                                Available Books
                            </h2>
                            
                            <x-search-bar />
                            
                                <nav class="flex items-center gap-4">
                                        <a
                                            href="{{ route('login') }}"
                                            class="inline-block px-5 py-1.5 text-gray-700 border border-transparent hover:border-gray-300 rounded-sm text-sm"
                                        >
                                            Log in
                                        </a>
                                            <a
                                                href="{{ route('register') }}"
                                                class="inline-block px-5 py-1.5 border border-gray-300 text-gray-700 rounded-sm text-sm hover:border-gray-400">
                                                Register
                                            </a>
                                </nav>
                        </div>
                    </div>
                </header>