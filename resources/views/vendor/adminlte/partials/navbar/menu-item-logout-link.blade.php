<li class="nav-item">
    <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit" class="nav-link btn btn-link">
            <i class="fa fa-fw fa-power-off text-red" aria-hidden="true"></i> Sair
        </button>
    </form>
</li>
