<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<style type="text/css">
			body { margin: 0; padding: 60pt 0 0; font-family: 'Helvetica'; color: #444; }
			#header { 
				position: fixed; top: -34pt; right: -34pt; left: -34pt; padding: 20pt 34pt 18pt; 
				background-color: #f0f0f0;
				border-bottom: 1px solid #ddd;
			}
			#header h1 { font-size: 20pt; margin: 0; }
			#header a { display: block; font-size: 10pt; }
			/*
			#footer { 
				position: fixed;
				bottom: -34pt; left: -34pt; right: -34pt; 
				padding: 12pt 34pt 0 34pt; height: 44pt; 
				font-size: 9.5pt; line-height: 1.2;
			}
			*/
			.field {
				margin: 0 0 15pt 0; position: relative; padding-left: 100pt;
			}
			.field.text {
				padding-bottom: 15pt;
				border-bottom: 1px solid #e0e0e0;
			}
			.field > div.title { 
				position: absolute; 
				left: 0; 
				top: 2pt; 
				width: 80pt; 
				font-size: 9pt;
			}
			p { margin: 0 0 10pt 0; }
			p:last-child { margin: 0; }
		</style>
	</head>
	<body>
		<div id="header">
			<h1>{{ $table->title }} #{{ $row->id }}</h1>
			<a href="{{ action('\LeftRight\Center\Controllers\RowController@edit', [$table->name, $row->id]) }}">{{ action('\LeftRight\Center\Controllers\RowController@edit', [$table->name, $row->id]) }}</a>
		</div>
		
		<div id="footer">
		</div>

		@foreach ($table->fields as $field)
			@if (!empty($row->{$field->name}) && 
				!in_array($field->type, ['files']) &&
				!in_array($field->name, ['updated_at', 'created_by', 'updated_by'])
				)
				<div class="field {{ $field->type }}">
					<div class="title">{{ $field->title }}</div>
				<?php
				switch ($field->type) {
					case 'email':
					echo '<p><a href="mailto:' . $row->{$field->name} . '">' . $row->{$field->name} . '</a></p>';
					break;

					case 'url':
					echo '<p><a href="' . $row->{$field->name} . '">' . $row->{$field->name} . '</a></p>';
					break;

					case 'datetime':
					echo '<p>' . date('M d, Y \a\t g:ia', strtotime($row->{$field->name})) . '</p>';
					break;

					case 'text':
					echo '<p>' . nl2br(trim($row->{$field->name})) . '</p>';
					break;

					default:
					echo '<p>' . trim($row->{$field->name}) . '</p>';
				}
				?>
				</div>
			@endif
		@endforeach
						
	</body>
</html>
<script type="text/php">
if ( isset($pdf) ) 
{
    $w = $pdf->get_width();
    $h = $pdf->get_height();
    $font = Font_Metrics::get_font("helvetica");
    $pdf->page_text($w -100, $h - 40, "Page {PAGE_NUM} of {PAGE_COUNT}", $font, 10, array(0,0,0));
}
</script>