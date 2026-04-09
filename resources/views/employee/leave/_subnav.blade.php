@php
  $tab = $leaveActiveTab ?? 'view';
@endphp
<nav class="leave-subnav" aria-label="Leave sections">
  <a href="{{ route('employee.leave.view') }}" class="leave-subnav-link {{ $tab === 'view' ? 'is-active' : '' }}"><i class="fa-solid fa-list-ul"></i> View my leave</a>
  <a href="{{ route('employee.leave.apply') }}" class="leave-subnav-link {{ $tab === 'apply' ? 'is-active' : '' }}"><i class="fa-solid fa-pen-to-square"></i> Apply for leave</a>
</nav>
