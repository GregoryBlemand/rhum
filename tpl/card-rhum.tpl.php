<!-- Un début de <div> existe de par la fonction dol_fiche_head() -->
	<input type="hidden" name="action" value="[view.action]" />
	<table width="100%" class="border">
		<tbody>
			<tr class="ref">
				<td width="25%">[langs.transnoentities(Ref)]</td>
				<td>[view.showRef;strconv=no]</td>
			</tr>

			<tr class="label">
				<td width="25%">[langs.transnoentities(Label)]</td>
				<td>[view.showLabel;strconv=no]</td>
			</tr>
			
			<tr class="fk_rhumerie">
				<td width="25%">Rhumerie</td>
				<td>[view.showFk_rhumerie;strconv=no]</td>
			</tr>
			
			<tr class="prix">
				<td width="25%">Prix</td>
				<td>[view.showPrix;strconv=no]</td>
			</tr>
			
		</tbody>
	</table>

</div> <!-- Fin div de la fonction dol_fiche_head() -->

[onshow;block=begin;when [view.mode]='edit']
<div class="center">
	
<!-- '+-' est l'équivalent d'un signe '>' (TBS oblige) -->
	[onshow;block=begin;when [object.getId()]+-0]
	<input type='hidden' name='id' value='[object.getId()]' />
	<input type="submit" value="[langs.transnoentities(Save)]" class="button" />
	[onshow;block=end]
	
	[onshow;block=begin;when [object.getId()]=0]
	<input type="submit" value="[langs.transnoentities(CreateDraft)]" class="button" />
	[onshow;block=end]
	
	<input type="button" onclick="javascript:history.go(-1)" value="[langs.transnoentities(Cancel)]" class="button">
	
</div>
[onshow;block=end]

[onshow;block=begin;when [view.mode]!='edit']
<div class="tabsAction">
	[onshow;block=begin;when [user.rights.rhum.write;noerr]=1]
	
			<div class="inline-block divButAction"><a href="rhum.php?id=[object.getId()]&action=edit" class="butAction">[langs.transnoentities(Modify)]</a></div>

		<div class="inline-block divButAction"><a href="rhum.php?id=[object.getId()]&action=clone" class="butAction">[langs.transnoentities(ToClone)]</a></div>
		
		<!-- '-+' est l'équivalent d'un signe '<' (TBS oblige) -->
			
			<div class="inline-block divButAction"><a href="rhum.php?id=[object.getId()]&action=delete" class="butActionDelete">[langs.transnoentities(Delete)]</a></div>
			

	[onshow;block=end]
</div>
[onshow;block=end]