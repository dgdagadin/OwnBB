<script type="text/javascript">
	jQuery(document).ready(function(){
		$('.BBEditor_Container').css('display', 'block');
	});
</script>

<div id="BBEditor_Container" class="BBEditor_Container">

	<!-- кнопки -->
	<div class="BBEditor_ButtonsDiv">
		<table cellspacing="0" cellpadding="0" border="0">
			<tr>
<?php foreach ($BBArray as $key=>$value) { ?>
				<td class="BBEditor_ButtonTD">
					<div style="width:30px; height:22px;" class="Button">
						<a onclick="<?php echo $value['Onclick']; ?>" title="<?php echo $value['Title']; ?>" href="javascript:void(0);"><img title="<?php echo $value['Title']; ?>" alt="" src="<?php echo $value['Source']; ?>" /></a>
					</div>
				</td>
<?php	if ($value['Type'] == 'default') { ?>
		
<?php	} else { ?>
		<span class="BBEditor_Divider">&nbsp;</span>
<?php	} ?>
<?php } ?>
			</tr>
		</table>
	</div>
	<!-- кнопки - конец -->

	<!-- смайлы -->
	<div id="BBEditor_Smiles" class="BBEditor_Hidden"></div>
	<!-- смайлы - конец -->

	<!-- цвета -->
	<div id="BBEditor_Color" class="BBEditor_Hidden">
		<table class="BBEditor_ColorTable" cellspacing="0" cellpadding="0">
			<tr class="BBEditor_ColorTR">
<?php if (sizeof ($ColorsArray) > 0) { ?>
<?php 	foreach ($ColorsArray as $key=>$value) { ?>
				<td class="BBEditor_ColorTD" style="background:<?php echo $value; ?>;">
					<a onclick="addTagColor ('<?php echo $FormID; ?>', '<?php echo $TextareaID; ?>', '<?php echo $value; ?>')" style="height:0;" href="javascript:void(0);"><img alt="" src="<?php echo OBB_IMAGE_DIR; ?>/spacer.gif" style="border:0; width:20px; height:20px;" /></a>
				</td>
<?php 	} ?>
<?php } ?>
			</tr>
		</table>
	</div>
	<!-- цвета - конец -->

</div>
