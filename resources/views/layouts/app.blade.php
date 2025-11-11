<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'User Auth App') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <nav class="navbar navbar-expand-lg bg-white border-bottom mb-4">
        <div class="container">
            <a class="navbar-brand" href="{{ route('dashboard') }}">{{ config('app.name', 'User Auth App') }}</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    @auth
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('dashboard') }}">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('users.index') }}">Users</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('employees.index') }}">Employees</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('products.index') }}">Products</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('customer.cart.index') }}">My Cart</a>
                        </li>
                    @endauth
                </ul>
                <ul class="navbar-nav ms-auto">
                    @auth
                        <li class="nav-item">
                            <span class="navbar-text me-3">Hi, {{ auth()->user()->name }}</span>
                        </li>
                        <li class="nav-item">
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger btn-sm">Logout</button>
                            </form>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('register') }}">Register</a>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    <main class="container">
        @if (session('status'))
            <div class="alert alert-success">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function() {
            const getCsrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            function handleStatusResponse(checkbox, badge, response) {
                const isActive = Boolean(response.status);
                checkbox.checked = isActive;

                if (badge) {
                    badge.textContent = response.label || (isActive ? 'Active' : 'Inactive');
                    badge.classList.toggle('bg-success', isActive);
                    badge.classList.toggle('bg-secondary', !isActive);
                }
            }

            function resolveErrorMessage(error) {
                if (typeof error === 'string') {
                    return error;
                }

                if (error?.message) {
                    return error.message;
                }

                if (error?.errors) {
                    const first = Object.values(error.errors)[0];
                    if (Array.isArray(first) && first.length > 0) {
                        return first[0];
                    }
                }

                return 'Unable to update status.';
            }

            window.bindStatusToggles = function bindStatusToggles(scope = document) {
                const csrf = getCsrfToken();
                if (!csrf) {
                    return;
                }

                scope.querySelectorAll('.status-toggle').forEach((toggle) => {
                    if (toggle.dataset.bound === 'true') {
                        return;
                    }

                    toggle.dataset.bound = 'true';
                    toggle.addEventListener('change', function() {
                        const checkbox = this;
                        const url = checkbox.dataset.url;
                        if (!url) {
                            return;
                        }

                        const desiredStatus = checkbox.checked ? 1 : 0;
                        const badge = checkbox.closest('tr')?.querySelector('.status-badge');
                        checkbox.disabled = true;

                        fetch(url, {
                                method: 'PATCH',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrf,
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                body: JSON.stringify({
                                    status: desiredStatus
                                }),
                            })
                            .then((response) => {
                                if (!response.ok) {
                                    return response
                                        .json()
                                        .then((data) => {
                                            throw data;
                                        })
                                        .catch((error) => {
                                            if (error && !(error instanceof Error)) {
                                                throw error;
                                            }

                                            throw new Error('Unable to update status.');
                                        });
                                }

                                return response.json();
                            })
                            .then((data) => {
                                handleStatusResponse(checkbox, badge, data);
                            })
                            .catch((error) => {
                                checkbox.checked = !checkbox.checked;
                                alert(resolveErrorMessage(error));
                            })
                            .finally(() => {
                                checkbox.disabled = false;
                            });
                    });
                });
            };

            window.initAsyncTable = function initAsyncTable(config) {
                if (!config) {
                    return;
                }

                const searchInput = config.searchInputId ?
                    document.getElementById(config.searchInputId) :
                    null;
                const sortBySelect = config.sortBySelectId ?
                    document.getElementById(config.sortBySelectId) :
                    null;
                const sortDirectionSelect = config.sortDirectionSelectId ?
                    document.getElementById(config.sortDirectionSelectId) :
                    null;
                const tableBody = config.tableBodyId ? document.getElementById(config.tableBodyId) : null;
                const paginationContainer = config.paginationId ?
                    document.getElementById(config.paginationId) :
                    null;

                if (!config.endpoint || !tableBody || !paginationContainer) {
                    return;
                }

                const buildParams = (overrides = {}) => {
                    const params = {
                        search: searchInput?.value?.trim() || undefined,
                        sort_by: sortBySelect?.value || undefined,
                        sort_direction: sortDirectionSelect?.value || undefined,
                        ...overrides,
                    };

                    Object.keys(params).forEach((key) => {
                        if (params[key] === undefined || params[key] === '') {
                            delete params[key];
                        }
                    });

                    return params;
                };

                const fetchData = (overrides = {}) => {
                    const params = buildParams(overrides);
                    const requestUrl = new URL(config.endpoint, window.location.origin);

                    Object.entries(params).forEach(([key, value]) => {
                        requestUrl.searchParams.set(key, value);
                    });

                    fetch(requestUrl.toString(), {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        })
                        .then((response) => {
                            if (!response.ok) {
                                throw new Error('Unable to load data.');
                            }

                            return response.json();
                        })
                        .then((data) => {
                            tableBody.innerHTML = data.body ?? '';
                            paginationContainer.innerHTML = data.pagination ?? '';
                            window.history.replaceState(null, '', requestUrl.toString());
                            window.bindStatusToggles(tableBody);
                            bindPaginationLinks();
                        })
                        .catch((error) => {
                            console.error(error);
                        });
                };

                function bindPaginationLinks() {
                    paginationContainer.querySelectorAll('a').forEach((link) => {
                        link.addEventListener('click', (event) => {
                            event.preventDefault();
                            const url = new URL(link.href, window.location.origin);
                            const page = url.searchParams.get('page');
                            fetchData({
                                page
                            });
                        });
                    });
                }

                let debounceTimer;
                const debounceFetch = () => {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => fetchData(), 300);
                };

                searchInput?.addEventListener('input', debounceFetch);
                sortBySelect?.addEventListener('change', () => fetchData());
                sortDirectionSelect?.addEventListener('change', () => fetchData());

                window.bindStatusToggles(tableBody);
                bindPaginationLinks();
            };
        })();
    </script>
    @stack('scripts')
</body>

</html>
