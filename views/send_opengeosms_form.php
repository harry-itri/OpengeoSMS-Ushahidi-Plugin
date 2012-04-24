<div class="report-description-text">
	<h5>Send OpenGeoSMS</h5> 
	<form method=POST action="<?php echo $action_url;?>" target="_blank">
		<input type=hidden name=incident_id value="<?php echo $incident_id;?>">
		<table bgcolor="#f5f5bc" width=100%>
			<tr height=30>
				<td>&nbsp;</td>
				<td width=100><strong>Phone Number</strong></td>
				<td><input type=text name="phone" size=20></td>
			</tr>
			<tr height=50>
				<td>&nbsp;</td>
				<td valign=top><strong>Additional Text</strong></td>
				<td><textarea cols=40 rows=3 name="text"><?php echo $text;?></textarea></td>
			</tr>
			<tr height=30>
				<td>&nbsp;</td>
				<td><input type="submit" value="Send"></td>
				<td>&nbsp;</td>
			</tr>
		</table>
	</form>
</div>