NodeEditor = {
	hook: function(node) {
		var self = this;
		node.addEventListener('keydown', self.keydown);
	},
	keydown: function(e) {
		if (e.keyCode === 9) {
			e.preventDefault();
			
			if (NodeEditor.is_selection_multiline(this)) {
				if (e.shiftKey === true) NodeEditor.handle_multiline_shifttab(this);
				else NodeEditor.handle_multiline_tab(this);
			} else {
				NodeEditor.handle_singleline_tab(this);
			}
		}
		if (e.keyCode === 13) {
			e.preventDefault();
			NodeEditor.handle_enter(this);
		}
	},
	is_selection_multiline: function(textarea) {
		var regex = new RegExp(/\n/);
		var selection = textarea.value.substring(textarea.selectionStart, textarea.selectionEnd);
		if (regex.test(selection)) return true;
		return false;
	},
	get_indent_indices: function(textarea) {
		var content = textarea.value;
		var selection_start = textarea.selectionStart;
		var selection_end = textarea.selectionEnd;
		var bols = [];
		var offset = 0;
		
		while(true) {
			var maches = content.match(/\n/);
			if (null == maches|| maches.length == 0) break;
			var bol = content.search("\n");
			var offset_last = offset;
			var offset = bol + offset + 1;
			if (offset > selection_end) break;
			if (offset > selection_start) {
				if (bols.length == 0) bols.push(offset_last);
				bols.push(offset);
			}
			content = content.substring(bol + 1);
		}
		return bols;
	},
	handle_enter: function(textarea) {
		var content = textarea.value.substring(0, textarea.selectionStart);
		var index = content.lastIndexOf("\n") + 1;
		content = content.substring(index) + "\n";
		var maches = content.match(/[^\t\ ]/);
		content = content.substring(0, maches.index);
		
		var event = document.createEvent('TextEvent');
		event.initTextEvent('textInput', true, true, null, "\n" + content, 0x09, "en-US");
		textarea.dispatchEvent(event);
	},
	handle_singleline_tab: function(textarea) {
		var selection_start = textarea.selectionStart;
		var selection_end = textarea.selectionEnd;
		textarea.value = textarea.value.substring(0, selection_start) + '\t' + textarea.value.substring(selection_end);
		textarea.selectionStart = textarea.selectionEnd = selection_start + 1;
	},
	handle_multiline_tab: function(textarea) {
		var selection_start = textarea.selectionStart;
		var selection_end = textarea.selectionEnd;
		var bols = NodeEditor.get_indent_indices(textarea);
		var count_inject = 0;
		var count_offset = 0;
		
		var index = bols.length;
		
		while (index --) {
			if (selection_start > bols[index]) count_offset++;
			
			textarea.value = textarea.value.substring(0, bols[index]) + '\t' + textarea.value.substring(bols[index]);
			count_inject ++;
		}
		textarea.selectionStart = selection_start + count_offset;
		textarea.selectionEnd = selection_end + count_inject;
	},
	handle_multiline_shifttab: function(textarea) {
		var content = textarea.value;
		var selection_start = textarea.selectionStart;
		var selection_end = textarea.selectionEnd;
		var bols = NodeEditor.get_indent_indices(textarea);
		var count_eject = 0;
		var count_offset = 0;
		
		var index = bols.length;
		
		while (index --) {
			var length_eject = 0;
			if (content.substring(bols[index], bols[index]+1) == '\t') length_eject = 1;
			if (length_eject == 0) {
				for (var i=0; i<4; i++) {
					if (content.substring(bols[index] + i, bols[index] + i + 1) == ' ') length_eject ++;
					else break;
				}
			}
			
			if (selection_start > bols[index]) count_offset += length_eject;
			
			if (length_eject == 0) continue;
			textarea.value = textarea.value.substring(0, bols[index]) + textarea.value.substring(bols[index] + length_eject);
			count_eject += length_eject;
		}
		textarea.selectionStart = selection_start - count_offset;
		textarea.selectionEnd = selection_end - count_eject;
	}
}