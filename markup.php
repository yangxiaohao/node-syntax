<?php

class NodeMarkup {

	var $code = 'none';
	var $block = 'code';
	
	function resolve ($string) {
		
		$regex = array (
			'(?<=^|[\r\n])(?:[\t]+[^\r\n]*(?:$|\R))+', // code
			'(?<=^|[\r\n])\[\!(?:[0-9a-zA-Z\=\,]+)\](?:$|\R)', // meta  '(?<=^|[\r\n])(?:[0-9]+\.)+ [^\r\n]*(?:$|\R)', // header
			'(?<=^|[\r\n])\r\n-{4,}(?:$|\R)', // hr
			'(?<=^|[\r\n])[^\r\n]+\R={4,}(?:$|\R)', // header1
			'(?<=^|[\r\n])[^\r\n]+\R-{4,}(?:$|\R)', // header2
			'(?<=^|[\r\n])[#]{3}[ ]{1}[^\r\n]+(?=$|\R)', // header3
			'(?<=^|[\r\n])[^\r\n]*(?:$|\R)', // others
		);
		$regex = '/('.(implode(')|(', $regex)).')/su';
		$content = preg_replace_callback($regex, 'self::_preg_callback', $string);
		return preg_replace('/\r\n/u', '<br />', $content);
	}
	
	function _preg_callback($matches) {
		$type = count($matches)-2;
		if ($type===0) {
			return $this->_preg_callback_block($matches);
		} else if ($type===1) {
			return $this->_preg_callback_meta($matches);
		} else if ($type===2) {
			return $this->_preg_callback_hr($matches);
		} else if ($type===3) {
			return $this->_preg_callback_h1($matches);
		} else if ($type===4) {
			return $this->_preg_callback_h2($matches);
		} else if ($type===5) {
			return $this->_preg_callback_h3($matches);
		} else {
			return $this->_preg_callback_text($matches);
		}
	}
	
	function _preg_callback_hr($matches) {
		return '<br /><hr />';
	}
	
	function _preg_callback_h1($matches) {
		$lines = preg_split ('/\r\n/u', $matches[0]);
		$block = '<span class="p_header1">'.$lines[0].'</span><br />';
		return $block;
	}
	
	function _preg_callback_h2($matches) {
		$lines = preg_split ('/\r\n/u', $matches[0]);
		$block = '<span class="p_header2">'.$lines[0].'</span><br />';
		return $block;
	}

	function _preg_callback_h3($matches) {
		$block = substr($matches[0], 4);
		$block = '<span class="p_header3">'.$block.'</span>';
		return $block;
	}
	
	function _preg_callback_text($matches) {
		$content = $matches[0];
		$content = htmlspecialchars($content);
		$content = preg_replace('/  /',' &nbsp;',$content);
		$content = preg_replace('/^ /','&nbsp;',$content);
		$content = preg_replace_callback('/(`|``)([^`].*?)\1/us', 'self::_preg_callback_inlinecode', $content);
		$content = preg_replace("/\[b\]((.|\n)*?)\[\/b\]/i", '<b>${1}</b>', $content);
		$content = preg_replace("/\[i\]((.|\n)*?)\[\/i\]/i", '<i>${1}</i>', $content);
		$content = preg_replace("/\[u\]((.|\n)*?)\[\/u\]/i", '<u>${1}</u>', $content);
		$content = preg_replace("/\[s\]((.|\n)*?)\[\/s\]/i", '<s>${1}</s>', $content);
		$content = preg_replace("/\[sup\](.+?)\[\/sup\]/is","<sup>\\1</sup>",$content);
		$content = preg_replace("/\[sub\](.+?)\[\/sub\]/is","<sub>\\1</sub>",$content);
		$content = preg_replace("/\[url\](.+?)\[\/url\]/is","<a href=\"\\1\" target='_blank'>\\1</a>",$content);
		$content = preg_replace("/\[url=(.+?)\](.+?)\[\/url\]/is","<a href='\\1' target='_blank'>\\2</a>",$content);
		$content = preg_replace("/\[img\](.+?)\[\/img\](\r\n){0,1}/is","<div class=\"image\"><img src=\\1></div>",$content);
		$content = preg_replace("/\[img\s(.+?)\](.+?)\[\/img\]/is","<img \\1 src=\\2>",$content);
		//$content = preg_replace('/(`|``)([^`].+?)\1/us','<span class="mono">\\2</span>',$content);
		
		return $content;
	}
	
	function _preg_callback_inlinecode($matches) {
		$content = $matches[2];
		$content = preg_replace('/\[/','&#91;',$content);
		$content = preg_replace('/\]/','&#93;',$content);
		return '<span class="mono">'.$content.'</span>';
	}
	
	function _preg_callback_meta($matches) {
		preg_match('/\[\!([0-9a-zA-Z\=\,]+)\]/u', $matches[0], $metasinfo);
		$metasinfo = $metasinfo[1];
		$metas = preg_split('/,/', $metasinfo);
		foreach ($metas as $meta) {
			$metainfo = preg_split('/=/', $meta);
			if (count($metainfo) == 2) { // key = value
				if ($metainfo[0] == 'code') {
					$this->code = $metainfo[1];
				}
			} else { // tag
				if ($meta == 'html') {
					$this->block = 'html';
				}
			}
		}
	}

	function _preg_callback_block($matches) {
		if ($this->block == 'html') {
			$this->block = 'code';
			$html = preg_replace('/\r\n/u', ' ', $matches[0]);
			return '<div class="html">'.$html.'</div>';
		} else {
			return $this->_preg_callback_code($matches);
		}
	}
	
	function _preg_callback_code($matches) {
		$content = $matches[0];
		$start_index = 0;
		$end_index = 0;
		
		$lines = preg_split ('/\r\n/u' , $content);
		$length = count($lines);
		
		
		if (substr($matches[0], 0, 2) == "\r\n") {
			$start_index = 1;
		}
		
		if (substr($matches[0], -2) == "\r\n") {
			$end_index = 1;
		}
		$content = '';
		$content_linetag = '';
		for ($i=$start_index; $i<$length - $end_index; $i++) {
			$lines[$i] = preg_replace('/[ ]{4}|\t/mu', '', $lines[$i], 1);
			$content .= $lines[$i]."\r\n";
			$content_linetag .= '<span>'.($i+1).'</span>';
		}
		
		$nodesyntax = new NodeSyntax();
		$content = htmlspecialchars($content);
		if ($this->code != 'none') {
			$content = $nodesyntax->highlight($content, $this->code);
		} else {
			$content = '<span class="c_g">'.$content.'</span>';
		}
		
		$block = ($start_index==1?"\r\n":'').'<div class="code"><table><tr><td class="c_lines">'.$content_linetag.'</td><td class="c_codes"><pre>'.$content.'</pre></td></tr></table></div>';
		return $block;
	}
}