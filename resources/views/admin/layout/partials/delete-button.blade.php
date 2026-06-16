@if(admin_can_delete())
    <form id="delete-form-{{ $id }}" action="{{ $action }}" method="POST" style="display:inline;">
        @csrf
        @method('DELETE')
        <button type="button" class="btn-danger btn btn-xs" onclick="confirmDelete({{ $id }})">
            <i class="fa fa-trash"></i> Delete
        </button>
    </form>
@else
    <button type="button" class="btn-danger btn btn-xs" disabled title="Delete is disabled for your role">
        <i class="fa fa-trash"></i> Delete
    </button>
@endif
