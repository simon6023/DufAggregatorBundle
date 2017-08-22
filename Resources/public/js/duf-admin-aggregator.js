function DufAdminAggregator() { }

DufAdminAggregator.prototype.loadMore = function(account_id, page)
{
	page 			= parseInt(page);
	var next_page  	= page + 1;
	var route 		= Routing.generate('duf_admin_aggregator_view_posts', { account_id: account_id, page: next_page });

	$.ajax({
		url: route,
		method: 'post',
		success: function(html) {
			// update posts list
			$('.posts-list').append(html);

			// update page number
			$('#aggregator-load-more').attr('data-page', next_page);

			// hide load more button if end of list
			if ($('.no-more-posts').length > 0)
				$('#aggregator-load-more').hide();
		},
		error: function(data) {
			console.log(data);
		},
	});
};

DufAdminAggregator.prototype.updatePostState = function(button)
{
	var post_id 		= button.parent().parent().parent().data('post-id');
	var current_state 	= button.attr('data-state');
	var new_state 		= (current_state === 1 || current_state === '1') ? 0: 1;
	var route 			= Routing.generate('duf_admin_aggregator_update_post_state', { post_id: post_id, state: new_state });

	$.ajax({
		url: route,
		method: 'post',
		success: function(response) {
			// update state data
			button.attr('data-state', new_state);

			// hide button
			var current_button_class 	= (current_state === 1 || current_state === '1') ? 'enable': 'disable';
			var new_button_class 		= (current_button_class === 'enable') ? 'disable': 'enable';

			button.find('.' + current_button_class).removeClass('hidden');
			button.find('.' + current_button_class).addClass('visible');

			button.find('.' + new_button_class).removeClass('visible');
			button.find('.' + new_button_class).addClass('hidden');
		},
		error: function(data) {
			console.log(data);
		},
	});
};

$(document).on('click', '#aggregator-load-more', function(e) {
	e.preventDefault();

	window.dufAdminAggregator.loadMore($(this).data('account-id'), $(this).attr('data-page'));
});

$(document).on('click', '.post .list-inline .post-state', function(e) {
	e.preventDefault();

	window.dufAdminAggregator.updatePostState($(this));
});