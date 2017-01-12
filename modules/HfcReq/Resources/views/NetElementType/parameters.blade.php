<!-- attach button -->
<div class='col-md-6'>
	{{ Form::open(['route' => ['NetElementType.assign', $view_var->id], 'method' => 'get']) }}
		{{ Form::submit('Assign Parameters', ['style' => 'simple']) }} <!-- .\App\Http\Controllers\BaseViewController::translate($view) -->
	{{ Form::close() }}
</div>

<!-- detach all button -->
<div class='col-md-6'>
	{{ Form::open(['route' => ['NetElementType.detach_all', $view_var->id], 'method' => 'delete']) }}
		{{ Form::submit('Detach All Parameters', ['!class' => 'btn btn-danger', 'style' => 'simple']) }} <!-- .\App\Http\Controllers\BaseViewController::translate($view) -->
	{{ Form::close() }}
</div>

<br><br>

<!-- list and detach button -->
{{ Form::open(array('route' => array('Parameter.destroy', 0), 'method' => 'delete')) }}

	<br>
	<table class="table">
		@foreach ($list as $param)
			<tr class="{{isset ($param->view_index_label()['bsclass']) ? $param->view_index_label()['bsclass'] : ''}}">
				<td> {{ Form::checkbox('ids['.$param->id.']', 1, null, null, ['style' => 'simple']) }} </td>
				<td> {{ HTML::linkRoute('Parameter.edit', is_array($param->view_index_label()) ? $param->view_index_label()['header'] : $param->view_index_label(), $param->id) }} </td>
			</tr>
		@endforeach
	</table>

	<!-- Delete button -->
	<div class='col-md-12'>
		{{ Form::submit('Delete', ['!class' => 'btn btn-danger', 'style' => 'simple']) }}
	</div>

{{ Form::close() }}
