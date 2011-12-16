jQuery(function($){
	var $group = $('#fee-group-post input');
	var $group_button = $('#fee-group-post-button input');

	$group.change(function(){
		$group_button.prop('disabled', ! $group.prop('checked') );
		if ( $group_button.prop('disabled') )
			$group_button.prop('checked', false );
	});

	$group.change();
});
