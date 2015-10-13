<table>
	<tr>
		<td>{{ Form::label('mac', 'MAC address') }}</td>
		<td>{{ Form::text('mac', 'foobar') }}</td>
		<td>{{ $errors->first('mac') }}</td>
	</tr>
	<tr>
		<td>{{ Form::label('hostname', 'Hostname') }}</td>
		<td>{{ Form::text ('hostname', null, array('readonly')) }}</td>
		<td>{{ $errors->first('hostname') }}</td>
	</tr>
	<tr>
		<td>{{ Form::label('modem_id', 'Modem') }}</td>
		<td>{{ Form::select('modem_id', $modems['data'], $modems['selected']) }}</td>
	</tr>
	<tr>
		<td>{{ Form::label('configfile_id', 'Configfile') }}</td>
		<td>{{ Form::select('configfile_id', $configfiles['data'], $configfiles['selected']) }}</td>
	</tr>
	<tr>
		<td>{{ Form::label('type', 'Typ') }}</td>
		<td>{{ Form::select('type', $mta_types['data'], $mta_types['selected']) }}</td>
	</tr>
</table>
