<div class="row mb-3 align-items-center">
    <div class="col-md-3 mb-2">
        <h5 class="mb-0">Total Users: <span class="badge bg-primary">{{ $total }}</span></h5>
    </div>
    <div class="col-md-6">
        <div class="row g-2">
            <div class="col-md-8">
                <div class="input-group">
                    <input type="text" class="form-control" id="userSearch" placeholder="Search by name or email..." value="{{ $search }}">
                    <button class="btn btn-primary" type="button" onclick="searchUsers()">
                        <i class="bi bi-search"></i> Search
                    </button>
                </div>
            </div>
            <div class="col-md-4 mb-2">
                <select class="form-select" id="statusFilter" onchange="changeStatus(this.value)">
                    <option value="active" {{ $status == 'active' ? 'selected' : '' }}>Active Only</option>
                    <option value="inactive" {{ $status == 'inactive' ? 'selected' : '' }}>Inactive Only</option>
                    <option value="all" {{ $status == 'all' ? 'selected' : '' }}>All Users</option>
                </select>
            </div>
        </div>
    </div>
    <div class="col-md-3 text-end">
        <a href="/admin/users/create" class="btn btn-success">
            <i class="bi bi-person-plus"></i> Create User
        </a>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>Avatar</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Registered</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @if(empty($users))
            <tr>
                <td colspan="7" class="text-center">No users found</td>
            </tr>
            @else
            @foreach($users as $user)
            <tr>
                <td>
                    @if($user->avatar)
                    <img src="/avatars/{{ $user->avatar }}"
                        class="rounded-circle"
                        width="40"
                        height="40"
                        style="object-fit: cover;"
                        alt="Avatar">
                    @else
                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center"
                        style="width: 40px; height: 40px; font-size: 1.2rem;">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    @endif
                </td>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>
                    @if($user->role == 1)
                    <span class="badge bg-danger">Admin</span>
                    @else
                    <span class="badge bg-primary">User</span>
                    @endif
                </td>
                <td>
                    <div class="form-check form-switch">
                        <input class="form-check-input"
                            type="checkbox"
                            {{ $user->active == 1 ? 'checked' : '' }}
                            {{ $user->id == \app\core\Session::user()->id ? 'disabled' : '' }}
                            onchange="toggleUserStatus({{ $user->id }}, this.checked, '{{ csrf_token() }}')">
                    </div>
                </td>
                <td>{{ date('Y-m-d', strtotime($user->created_at)) }}</td>
                <td>
                    <a href="/admin/users/{{ $user->id }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                    @if($user->id != \app\core\Session::user()->id)
                    <button type="button" class="btn btn-sm btn-outline-danger"
                        onclick="confirmDeleteUser({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ csrf_token() }}')">
                        <i class="bi bi-trash"></i> Delete
                    </button>
                    @endif
                </td>
            </tr>
            @endforeach
            @endif
        </tbody>
    </table>
</div>

<!-- Pagination Controls -->
<div class="row mt-3">
    <div class="col-md-6">
        <div class="d-flex align-items-center">
            <label class="me-2">Show:</label>
            <select class="form-select form-select-sm w-auto" onchange="changePerPage(this.value)">
                <option value="5" {{ $perPage == 5 ? 'selected' : '' }}>5</option>
                <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>20</option>
                <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                <option value="all" {{ $perPage == 'all' ? 'selected' : '' }}>All</option>
            </select>
            <span class="ms-2">per page</span>
        </div>
    </div>
    <div class="col-md-6">
        @if($totalPages > 1)
        <nav aria-label="User pagination">
            <ul class="pagination pagination-sm justify-content-end mb-0">
                <li class="page-item {{ $page <= 1 ? 'disabled' : '' }}">
                    <a class="page-link" href="#" onclick="goToPage({{ $page - 1 }}); return false;">Previous</a>
                </li>

                @php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                @endphp

                @for($i = $startPage; $i <= $endPage; $i++)
                    <li class="page-item {{ $i == $page ? 'active' : '' }}">
                    <a class="page-link" href="#" onclick="goToPage({{ $i }}); return false;">{{ $i }}</a>
                    </li>
                    @endfor

                    <li class="page-item {{ $page >= $totalPages ? 'disabled' : '' }}">
                        <a class="page-link" href="#" onclick="goToPage({{ $page + 1 }}); return false;">Next</a>
                    </li>
            </ul>
        </nav>
        @endif
    </div>
</div>

<script>
    // Update state from server values (functions are defined in dashboard view)
    if (window.adminUsers) {
        window.adminUsers.currentPage = {!! $page !!};
        window.adminUsers.currentPerPage = '{!! $perPage !!}';
        window.adminUsers.currentSearch = '{!! addslashes($search) !!}';
        window.adminUsers.currentStatus = '{!! $status !!}';
    }

    // Re-attach Enter key listener for search (in case DOM was replaced)
    const searchInput = document.getElementById('userSearch');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                if (window.searchUsers) {
                    window.searchUsers();
                }
            }
        });
    }
</script>