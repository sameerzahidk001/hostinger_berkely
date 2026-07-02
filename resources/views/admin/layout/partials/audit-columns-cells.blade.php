<td data-order="{{ $model->created_at ? \Carbon\Carbon::parse($model->created_at)->timestamp : 0 }}">{{ $model->created_at ? \Carbon\Carbon::parse($model->created_at)->format('M d, Y H:i') : '-' }}</td>
<td data-order="{{ audit_user_name($model->createdBy ?? null, $model->created_by ?? null) }}">{{ audit_user_name($model->createdBy ?? null, $model->created_by ?? null) }}</td>
<td data-order="{{ $model->updated_at ? \Carbon\Carbon::parse($model->updated_at)->timestamp : 0 }}">{{ $model->updated_at ? \Carbon\Carbon::parse($model->updated_at)->format('M d, Y H:i') : '-' }}</td>
<td data-order="{{ audit_user_name($model->updatedBy ?? null, $model->updated_by ?? null) }}">{{ audit_user_name($model->updatedBy ?? null, $model->updated_by ?? null) }}</td>
