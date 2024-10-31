(function($){
	$(function(){
		
		$('.popular-chips-exclude-select').siblings('a.edit-popular-chips-exclude').click(function() {
			if ($(this).siblings('.popular-chips-exclude-select').is(":hidden")) {
				$(this).siblings('.popular-chips-exclude-select').slideDown("normal");
				$(this).hide();
			}
			return false;
		});
		
		$('.save-popular-chips-exclude', '.popular-chips-exclude-select').click(function() {
			var select = $(this).parent('.popular-chips-exclude-select');
			var checked = $('.popular-chips-exclude',select).is(":checked");
			select.slideUp("normal");
			$(this).siblings('.hidden-popular-chips-exclude').val(checked?'1':'0');
			select.siblings('.popular-chips-display').toggle(!checked);
			select.siblings('.popular-chips-exclude-display').toggle(checked);
			select.siblings('a.edit-popular-chips-exclude').show();
			return false;
		});
		
		$('.cancel-popular-chips-exclude', '.popular-chips-exclude-select').click(function() {
			var select = $(this).parent('.popular-chips-exclude-select');
			select.slideUp("normal");
			if($(this).siblings('.hidden-popular-chips-exclude').val()=='1')
				$('.popular-chips-exclude',select).attr('checked','checked');
			else
				$('.popular-chips-exclude',select).removeAttr('checked');
			select.siblings('a.edit-popular-chips-exclude').show();
			return false;
		});
		
		
		var countSelect = $('#popular-chips-count-select');
		
		countSelect.siblings('a.edit-popular-chips-count').click(function() {
			if (countSelect.is(":hidden")) {
				countSelect.slideDown("normal");
				$(this).hide();
			}
			return false;
		});
		
		$('.save-popular-chips-count', countSelect).click(function() {
			countSelect.slideUp("normal");
			$('#hidden-popular-chips-count').val($('#popular-chips-count').val());
			$('#popular-chips-count-display').text($('#popular-chips-count').val().replace( /([0-9]+?)(?=(?:[0-9]{3})+$)/g , '$1,' ));
			$('#hidden-popular-chips-disable').val($('#popular-chips-disable').is(":checked")?'1':'0');
			$('#popular-chips-disable-display').toggle($('#popular-chips-disable').is(":checked"));
			countSelect.siblings('a.edit-popular-chips-count').show();
			return false;
		});
		
		$('.cancel-popular-chips-count', countSelect).click(function() {
			countSelect.slideUp("normal");
			$('#popular-chips-count').val($('#hidden-popular-chips-count').val());
			if($('#hidden-popular-chips-disable').val()=='1')
				$('#popular-chips-disable').attr('checked','checked');
			else
				$('#popular-chips-disable').removeAttr('checked');
			countSelect.siblings('a.edit-popular-chips-count').show();
			return false;
		});
		
		$('#hidden-popular-chips-count').val($('#original-popular-chips-count').val());
		$('#popular-chips-count').val($('#original-popular-chips-count').val());
	});
})(jQuery);
