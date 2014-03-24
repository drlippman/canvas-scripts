//item type selector
var curitemtype = 'none';
var itemselloaded = false;
var txtchgtype = 'searchreplace';

function chkall(v) {
	$('.itemsel').prop("checked",v);
	return false;
}
function updateTochg(el) {
	var val = el.value;
	
	if (val == 'none') {
		$('#itemselbox').hide();
		$('#itemtochgbox').hide();
		$('#mcbox').hide();
		$('#textchgbox').hide();
		$('#testout').html('');
	} else if (val != curitemtype) {
		if ($('#domain').val()=='' || $('#cid').val()=='' || $('#token').val()=='') {
			alert('Did you forget something?  Make sure you put in all the required info.');
			$('#tochg').val("none");
			return;
		}
		$('#itemselbox').show();
		$('#itemsellist').html('<li>Loading...</li>');
		loaditemlist(val);
		$('#itemtochgbox').hide();
		$('#mcbox').hide();
		$('#textchgbox').hide();
		updateItemTochg(val);
	}
	curitemtype = val;
}

var iteminfo = {
	'assignments': [{
		'val': 'description',
		'lbl': 'Description',
		'type': 'text'
	}],
	'files': [{
		'val': 'locked',
		'lbl': 'Locked / Unlocked',
		'type': 'mc',
		'optval': ['false','true'],
		'optlbl': ['Unlocked', 'Locked']
		}
	],
	'pages': [{
			'val': 'body',
			'lbl': 'Content body',
			'type': 'text'
		}
	],
	'discuss': [{
			'val': 'message',
			'lbl': 'Message body',
			'type': 'text'
		}
	],
	'quizzes': [{
			'val': 'description',
			'lbl': 'Description (Instructions)',
			'type': 'text'
		},
		{
			'val': 'time_limit',
			'lbl': 'Time limit, in minutes (blank for none)',
			'type': 'shortval'
		},
		{
			'val': 'hide_results',
			'lbl': 'Hide results',
			'type': 'mc',
			'optval': ["null", "always", "until_after_last_attempt"],
			'optlbl': ['No', 'Always', 'Until after last attempt']
		}
	]
};
			
//sets the item-specific options
function updateItemTochg(val) {
	var html = '<option value="none">Select...</option>';
	for (var i=0;i<iteminfo[val].length;i++) {
		html += '<option value="'+iteminfo[val][i].val+'">'+iteminfo[val][i].lbl+'</option>';
	}
	$('#attrsel').html(html);
	$('#itemtochgbox').show();
}

//
var curattrtype = '';
function updateAttrSel(el) {
	var attr = el.value;
	for (var i=0;i<iteminfo[curitemtype].length;i++) {
		if (iteminfo[curitemtype][i].val==attr) {
			var attrtype = iteminfo[curitemtype][i].type;
			if (attrtype == 'mc') {
				curattrtype = 'mc';
				$('#textchgbox').hide();
				var html = 'Value for '+iteminfo[curitemtype][i].lbl+': ';
				html += '<select id="mc">';
				for (var j=0;j<iteminfo[curitemtype][i].optval.length;j++) {
					html += '<option value="'+iteminfo[curitemtype][i].optval[j]+'">'+iteminfo[curitemtype][i].optlbl[j]+'</option>';
				}
				html += '<select>';
				$('#mcbox').html(html).show();
			} else if (attrtype == 'shortval') {
				curattrtype = 'shortval';
				$('#textchgbox').hide();
				var html = 'Value for '+iteminfo[curitemtype][i].lbl+': ';
				html += '<input type="text" size="20" id="shortval"/>';
				$('#mcbox').html(html).show();
			} else if (attrtype=='text') {
				curattrtype = 'text';
				$('#mcbox').hide();
				$('#textchgbox').show();
			}
			break;
		}
	}
	$('#testout').html("");
}

var toloadlist = [];
var pagelist = [];
//load item selection list
function loaditemlist(type) {
	var cid = $('#cid').val();
	var token = encodeURIComponent($('#token').val());
	var domain = encodeURIComponent($('#domain').val());
	
	//base on curitemtype
	//do ajax to jquery, add to id=itemsellist with checkboxes
	$.ajax( {
		type: "POST",
		url: 'canvassearch.php',
		data: 'do=getlist&domain='+domain+'&cid='+cid+'&type='+type+'&token='+token
		})
		.done(function(data) {
			$('#itemsellist').html(data);
		});
	$('#exbtn').show();
}

//update type of text change
function updateTxtChg(el) {
	txtchgtype = el.value;
	$('#searchreplace,#replace,#append,#regex').hide();
	$('#'+txtchgtype).show();
	$('#testout').html("");
}

//test run
function testrun() {
	$('#testout').html('Working...');
	//grab first item checked, or pull first item from list of items
	
	var cid = $('#cid').val();
	var token = encodeURIComponent($('#token').val());
	var domain = encodeURIComponent($('#domain').val());
	var checked = $('.itemsel:checked');
	var attr = $('#attrsel').val();
	
	if (checked.length==0) {alert("No items selected"); return;}
	var item = encodeURIComponent($('.itemsel:checked:first').val());
	
	var txttype = $('#txtchgmethod').val();
	if (txttype=='searchreplace') {
		txtdet = '&src='+encodeURIComponent($('#searchfor').val())+'&rep='+encodeURIComponent($('#replacewith').val());
	} else if (txttype=='replace') {
		txtdet = '&rep='+encodeURIComponent($('#replacetxt').val());
	} else if (txttype=='append') {
		txtdet = '&app='+encodeURIComponent($('#appendtxt').val());
	} else if (txttype=='regex') {
		txtdet = '&src='+encodeURIComponent($('#regexsearch').val())+'&rep='+encodeURIComponent($('#regexreplace').val());
	}
	//base on curitemtype
	//do ajax to jquery, add to id=itemsellist with checkboxes
	$('#testout').html("Running...");
	$.ajax( {
		type: "POST",
		url: 'canvassearch.php',
		data: 'do=test&domain='+domain+'&cid='+cid+'&type='+curitemtype+'&item='+item+'&attr='+attr+'&txttype='+txttype+txtdet+'&token='+token
		})
		.done(function(data) {
			$('#testout').html(data);
		}).fail(function(data) {
			$('#testout').html("Failure. "+data);
		});
		
	//execute change against item

}

//execute
function runchange() {
	$('#testout').html('Working...');
	
	var cid = $('#cid').val();
	var token = encodeURIComponent($('#token').val());
	var domain = encodeURIComponent($('#domain').val());
	var checked = $('.itemsel');
	var attr = $('#attrsel').val();
	
	var items = [];
	
	$('.itemsel:checked').each(function() {items.push($(this).val());});
	if (items.length==0) {alert("no items selected"); return;}
	var itemlist = items.join(':::');
	
	if (curattrtype=='text') {
		var txttype = $('#txtchgmethod').val();
		dataext = '&txttype='+txttype;
		if (txttype=='searchreplace') {
			dataext += '&src='+encodeURIComponent($('#searchfor').val())+'&rep='+encodeURIComponent($('#replacewith').val());
		} else if (txttype=='replace') {
			dataext += '&rep='+encodeURIComponent($('#replacetxt').val());
		} else if (txttype=='append') {
			dataext += '&app='+encodeURIComponent($('#appendtxt').val());
		} else if (txttype=='regex') {
			dataext += '&src='+encodeURIComponent($('#regexsearch').val())+'&rep='+encodeURIComponent($('#regexreplace').val());
		}
	} else if (curattrtype=='shortval') {
		dataext = '&val='+encodeURIComponent($('#shortval').val());
	} else if (curattrtype=='mc') {
		dataext = '&val='+encodeURIComponent($('#mc').val());
	}
	//base on curitemtype
	//do ajax to jquery, add to id=itemsellist with checkboxes
	$('#testout').html("Running...");
	$.ajax( {
		type: "POST",
		url: 'canvassearch.php',
		data: 'do=execute&domain='+domain+'&cid='+cid+'&type='+curitemtype+'&items='+itemlist+'&attr='+attr+dataext+'&token='+token
		})
		.done(function(data) {
			$('#testout').html(data);
		}).fail(function(data) {
			$('#testout').html("Failure. "+data);
		});
		
}
