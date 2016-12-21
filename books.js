$.fn.clear = function(states) {
	states = states ? states : ['danger', 'primary', 'success'];
	for (var key in states) { $(this).removeClass('toast-' + states[key]); }
};

var settings = { loading : false };

$(function() {
	$('#run').css({
		'margin-left': Math.ceil($(document).width() / 2),
		'margin-top': Math.ceil($(document).height() / 3)
	}).hide();

	$.ajaxSetup({
		type       : 'post',
		dataType   : 'json',
		url        : 'ajax.php',
		cache      : false,
		timeout    : 300000,
		beforeSend : function() { $('#run').show(); },
		complete   : function() { settings.loading = false; $('#run').hide(); },
		error      : function(xhr, status) { if (status == 'timeout') alert('Превышено время ожидания ответа'); }
	});

	$.each($('button[data-action]'), function() {
		$(this).on('click', {'action': $(this).data('action')}, actionHandler);
	});

	$('#files').fileupload({
		url         : 'upload.php',
		dataType    : 'json',
		start       : function(e, data) {
			$('#progress').empty();
		},
		send        : function(e, data) {
			$('#progress').append('<div class="progress"><div class="progress-bar progress-bar-success">' + data.files[0].name + '</div></div>')
		},
		done        : function(e, data) {
			var r = data.result.shift(), $log = $('#log');
			$log.clear();
			if (r.ok) {
				$log.text(r.fname + ' uploaded').addClass('toast-success');
			} else {
				$log.text('uploading error: ' + r.fname + (r.err ? (' (' + r.err + ')') : '')).addClass('toast-danger');
			}
		},
		progressall : function(e, data) {
			$('#progress').find('div.progress').remove();
		}
	});

	$('button[data-action="books"]').trigger('click');
});

function actionHandler(e) {
	if (settings.loading) return;

	var post = {}, $log = $('#log');
	$.each(e.data, function(key, val) { post[key] = val; });
	try { console.log(post) } catch(err) {};

	if (post.action == 'books') {
		var $out = $('#books'); $out.empty();

		$log.empty().clear();
		settings.loading = true;

		$.ajax({
			data    : post,
			success : function(data) {
				if (data.ok) {
					if (data.hasOwnProperty('authors')) {
						if (data.authors.length == 0) $log.text('no authors found').addClass('toast-danger');

						$.each(data.authors, function() {
							var h = [];
							h.push('<tr><td><a class="text-bold" href="javascript:void(0)" data-author="' + this.hash + '">' + this.author + '</a></td>');
							h.push('<td class="col-2">' + this.count + ' books</td></tr>');
							$out.append(h.join(''));
						});

						$.each($out.find('a'), function() {
							var params = {'action': 'books', 'author': $(this).data('author')};
							$(this).on('click', params, actionHandler);
						});
					} else if (data.hasOwnProperty('books')) {
						if (data.books.length == 0) $log.text('no books found').addClass('toast-danger');

						$.each(data.books, function() {
							var h = [];
							h.push('<tr data-id="' + this.id + '">');
							h.push('<td>' + (this.file.length ? ('<a href="https://drive.google.com/uc?export=download&amp;id=' + this.file + '">' + this.title + '</a>') : this.title) + '</td>');
							h.push('<td class="col-2">' + this.size + '</td><td class="col-1">');
							h.push('<button class="btn btn-block btn-sm">delete</button>');
							h.push('</td></tr>');
							$out.append(h.join(''));
						});

						$.each($out.find('button'), function() {
							var params = {'action': 'gdrive.delete', 'id': $(this).parents('tr').data('id')};
							$(this).on('click', params, actionHandler);
						});
					} else if (data.hasOwnProperty('letters')) {
						if (data.letters.length == 0) $log.text('no letters found').addClass('toast-danger');

						var h = [];
						$.each(data.letters, function() {
							h.push('<li class="page-item">');
							h.push('<a class="text-bold tooltip" href="javascript:void(0)" data-letter="' + this.number + '" data-tooltip="' + this.count + ' authors">' + this.letter + '</a>');
							h.push('</li>');
						})
						$('#letters').html(h.join(''));

						$.each($('#letters').find('a'), function() {
							var params = {'action': 'books', 'letter': $(this).data('letter')};
							$(this).on('click', params, actionHandler);
						});
					}
				} else {
					$log.text(data.err).addClass('toast-danger');
				}
			}
		});
	} else if (post.action == 'dbase.gdrive' || post.action == 'gdrive.dbase') {
		$log.empty().clear();
		settings.loading = true;

		$.ajax({
			data    : post,
			success : function(data) {
				if (data.ok === false) {
					$log.text(data.err).addClass('toast-danger');
				} else if (post.action == 'dbase.gdrive') {
					$log.text(data.books.length + ' books deleted').addClass('toast-success');
				} else {
					$log.text(data.books.length + ' missing books added').addClass('toast-success');
				}
			}
		});
	} else if (post.action == 'gdrive') {
		$log.empty().clear();
		settings.done = 0;
		actionHandler({'data': {'action': 'gdrive.upload'}});
	} else if (post.action == 'gdrive.upload') {
		settings.loading = true;

		$.ajax({
			data    : post,
			success : function(data) {
				$log.clear()

				if (data.ok) {
					settings.done ++;

					if (data.left) {
						$log.text(settings.done + ' books uploaded, ' + data.left + ' books left').addClass('toast-primary');

						settings.loading = false;
						actionHandler({'data': {'action': 'gdrive.upload'}});
					} else {
						$log.text(settings.done + ' books uploaded').addClass('toast-success');
					}
				} else {
					$log.text(data.err).addClass('toast-danger');
				}
			}
		});
	} else if (post.action == 'gdrive.delete') {
		var $this = $('#books').find('tr[data-id="' + post.id + '"]'), title = $this.find('td').eq(0).text();
		if (settings.loading || !confirm('really want delete «' + title + '»?')) return;

		$log.empty().clear();
		settings.loading = true;

		$.ajax({
			data    : post,
			success : function(data) {
				if (data.ok) $this.remove();
				$log.text(data.ok ? 'book deleted' : data.err).addClass('toast-' + (data.ok ? 'success' : 'danger'));
			}
		});
	} else if (post.action == 'opds') {
		$log.empty().clear();
		settings.loading = true;

		$.ajax({
			data    : post,
			success : function(data) {
				$log.text(data.ok ? 'OPDS generated' : data.err).addClass('toast-' + (data.ok ? 'success' : 'danger'));
			}
		});
	}
}