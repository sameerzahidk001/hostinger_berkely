<td data-order="{{ $model->created_at ? \Carbon\Carbon::parse($model->created_at)->timestamp : 0 }}">{{ $model->created_at ? \Carbon\Carbon::parse($model->created_at)->format('M d, Y H:i') : '-' }}</td>
<td>{{ audit_user_name($model->createdBy ?? null, $model->created_by ?? null) }}</td>
<td>{{ $model->updated_at ? \Carbon\Carbon::parse($model->updated_at)->format('M d, Y H:i') : '-' }}</td>
<td>{{ audit_user_name($model->updatedBy ?? null, $model->updated_by ?? null) }}</td>
