<?php

/* Configuration */

define('SITE_URL', 'https://neatnik.net/dhammapada/');
define('SITE_PATH', '/var/www/html/dhammapada/');
define('DEFAULT_TRANSLATION', 'thanissaro');

/* Functions */

function output($content) {
	global $output;
	$content = str_replace("\\r", null, $content);
	$content = str_replace("\\n", '<br>', $content);
	$output .= "\n".$content;
}

function highlight($str, $search) {
	$occurrences = substr_count(strtolower($str), strtolower($search));
	$newstring = $str;
	$match = array();
	for ($i=0;$i<$occurrences;$i++) {
		$match[$i] = stripos($str, $search, $i);
		$match[$i] = substr($str, $match[$i], strlen($search));
		$newstring = str_replace($match[$i], '[#]'.$match[$i].'[@]', strip_tags($newstring));
	}
	$newstring = str_replace('[#]', '<strong style="font-weight: normal; background: #fff0b9;">', $newstring);
	$newstring = str_replace('[@]', '</strong>', $newstring);
	return $newstring;
}

// Initialize output
$output = null;

// Parse the URI
$uri = $_SERVER['REQUEST_URI'];
$uri = explode('/', $uri);
$uri = array_slice($uri, 2);

// Redirect the verse 1 if nothing specific is being requested
if($uri[0] == null) {
	header('Location: '.SITE_URL.'gatha/1');
	exit;
}

// Redirect random verse requests
if($uri[0] == 'random') {
	header('Location: '.SITE_URL.'gatha/'.rand(1,423));
	exit;
}

// Load chapters
$chapters = json_decode(file_get_contents(SITE_PATH.'text/meta_chapters.json'));

/* Verse view */

if($uri[0] == 'gatha') {
	$verse = $uri[1];
	$verse = $verse + 0; // Normalize
	
	$translation = isset($uri[3]) ? $uri[3] : DEFAULT_TRANSLATION;
	if($translation == 'thero') $translation = 'weragoda'; // Correcting a previous error
	
	// Load verses
	$verses = json_decode(file_get_contents(SITE_PATH.'text/text_dhammapada.json'));
	if(!isset($verses->$verse)) { // Redirect if this verse doesn't exist
		header('Location: '.SITE_URL.'gatha/1');
		exit;
	}
	
	// Load translators
	$translators = json_decode(file_get_contents(SITE_PATH.'text/meta_translators.json'));
	
	// Load glossary
	$glossary = json_decode(file_get_contents(SITE_PATH.'text/meta_glossary.json'));
	
	// Set current chapter and text
	$chapter = $verses->$verse->chapter;
	$text = $verses->$verse->text;
	
	// Prepare navigation bar
	$previous_verse = $verse == 1 ? 423 : $verse - 1;
	$next_verse = $verse == 423 ? 1 : $verse + 1;
	
	$nav = '<nav class="even-smaller">';
	$nav .= '<ul class="horizontal">';
	$nav .= '<li><a href="/dhammapada/gatha/'.$previous_verse.'/translation/'.$translation.'"><i class="fas fa-arrow-alt-circle-left"></i> Previous</a></li>';
	$nav .= '<li><a href="/dhammapada/gatha/'.$next_verse.'/translation/'.$translation.'">Next <i class="fas fa-arrow-alt-circle-right"></i></a></li>';
	$nav .= '<li style="margin: 0 1em 0 0.5em;"><a href="/dhammapada/random"><i class="fas fa-random"></i> Random</a></li>';
	$nav .= '<li style="margin: 0 1em 0 0;"><a href="/dhammapada/chapter/'.$chapter.'"><i class="far fa-th-large"></i> Chapter '.$chapter.'</a></li>';
	$nav .= '<li><a href="/dhammapada/verses/"><i class="far fa-stop"></i> Verse '.$verse.'</a></li>';
	$nav .= '<li><form style="margin: 0 0 0 1em; padding: 0; display: inline;" action="/dhammapada/search/" method="post"><i class="fas fa-search"></i> <input style="font-size: inherit; width: 5em; margin: 0; padding: 0; border: 0; background: inherit; border-bottom: 1px solid var(--foreground); border-radius: 0;" type="text" name="query" id="query"></form></li>';
	$nav .= '</ul>';
	$nav .= '</nav>';
	
	output($nav);
	
	output('<h2>'.$chapters->$chapter->pali.' ⧸ Gāthā '.$verse.'</h2>');
	
	if(file_exists(SITE_PATH.'/audio/anandajoti/'.$verse.'.mp3')) {
		$pali_audio = '<p><audio controls><source src="/dhammapada/audio/anandajoti/'.$verse.'.mp3" /><a href="/dhammapada/audio/anandajoti/'.$verse.'.mp3">Gāthā '.$verse.'</a></audio></p>';
	}
	
	else {
		$pali_audio = null;
	}
	
	// Process inline glossary data
	$processed = array();
	
	//$text = explode(' ', $text);
	$lines = explode("\\n", $text);
	
	foreach($lines as $line) {
		$text = explode(' ', $line);
		$i = 1;
		foreach($text as $word) {
			$word_tmp = str_replace("\\n", '', strtolower($word));
			$newline = $i == count($text) ? '<br>' : null;
			if(isset($glossary->$word_tmp)) {
				$processed[] = '<a class="clickable word" data-part="'.$glossary->$word_tmp->part.'" data-definition="'.$glossary->$word_tmp->definition.'">'.$word.'</a>'.$newline;
			}
			$i++;
		}
	}
	
	$text = implode(' ', $processed);
	
	$info = '<p class="even-smaller"><small><i class="fas fa-info-circle"></i> Click any Pāli word for an inline definition.</small></p>';
	
	output('<div>'.$text.$info.$pali_audio.'</div>');
	
	// Prepare translation bar
	$translation_nav = '<nav class="even-smaller">';
	$translation_nav .= '<ul class="horizontal">';
	foreach($translators as $translator => $data) {
		if($translation == $translator) {
			$style = 'color: #000;';
		}
		else {
			$style = null;
		}
		
		if($data->type == 'text') $icon = '<i class="fal fa-file-alt"></i>';
		if($data->type == 'audio') $icon = '<i class="fal fa-waveform"></i>';
		
		$translation_nav .= '<li><a style="'.$style.'" href="/dhammapada/gatha/'.$verse.'/translation/'.$translator.'">'.$icon.' '.$data->label.'</a></li> ';
	}
	$translation_nav .= '</ul>';
	$translation_nav .= '</nav>';
	
	output($translation_nav);
	output('<h2>'.$chapters->$chapter->english.' ⧸ Verse '.$verse.'</h2>');
	
	// Load translation
	if(isset($translators->$translation->type) && $translators->$translation->type == 'audio') {
		
		if(file_exists(SITE_PATH.'audio/fronsdal/'.$verse.'.mp3')) {
			$translation_text = '<p>Gil Fronsdal’s reading of verse '.$verse.' at the <a href="https://www.insightmeditationcenter.org/books-articles/">Insight Meditation Center</a>:</p><audio controls><source src="/dhammapada/audio/fronsdal/'.$verse.'.mp3" /><a href="/dhammapada/audio/fronsdal/'.$verse.'.mp3">Verse '.$verse.'</a></audio>';
		}
		else {
			$translation_text = '<p><img class="emoji" src="https://twemoji.maxcdn.com/2/svg/1f626.svg" alt=""> This audio is not yet available.</p>';
		}
		output($translation_text);
	}
	if(isset($translators->$translation->type ) && $translators->$translation->type == 'text') {
		$translation_text = json_decode(file_get_contents(SITE_PATH.'text/translation_'.$translation.'.json'));
		output('<div style="white-space: pre-wrap; word-wrap: break-word;">'.$translation_text->$verse.'</div>');
	}
	
	$license = isset($translators->$translation->license) ? $translators->$translation->license : null;
	
	output('<div><small>'.$license.'</small></div>');
}

/* Chapter list */

if($uri[0] == 'chapters') {
	output('<h2>Vagga ⧸ Chapter</h2>');
	output('<ol>');
	foreach($chapters as $i => $data) {
		output('<li><a href="/dhammapada/vagga/'.$i.'">'.$data->pali.'</a> ⧸ <a href="/dhammapada/chapter/'.$i.'">'.$data->english.'</a></li>');
	}
	output('</ol>');
	
}

/* Chapters view */

if($uri[0] == 'chapter' || $uri[0] == 'vagga') {
	$chapter = $uri[1];
	output('<h2>'.$chapters->$chapter->pali.' ⧸ '.$chapters->$chapter->english.'</h2>');
	
	// Load verses
	$verses = json_decode(file_get_contents(SITE_PATH.'text/text_dhammapada.json'));
	
	// Load translation
	$translation_text = json_decode(file_get_contents(SITE_PATH.'text/translation_'.DEFAULT_TRANSLATION.'.json'));
	
	$found = false;
	foreach($verses as $verse => $data) {
		if($data->chapter == $chapter) {
			if(!$found) {
				output('<ol start="'.$verse.'">');
				$found = true;
			}
			$first_line_pali = substr(explode("\\n", $data->text)[0], 0, -1);
			$first_line_english = explode("\\n", $translation_text->$verse)[0];
			output('<li><a href="/dhammapada/gatha/'.$verse.'">'.$first_line_pali.'</a> ⧸ <a href="/dhammapada/gatha/'.$verse.'">'.$first_line_english.'</a></li>');
		}
	}
	output('</ol>');
}


/* Verses view */

if($uri[0] == 'verses') {
	output('<h2>Gāthā</h2>');
	
	// Load verses
	$verses = json_decode(file_get_contents(SITE_PATH.'text/text_dhammapada.json'));
	
	// Load translation
	$translation_text = json_decode(file_get_contents(SITE_PATH.'text/translation_'.DEFAULT_TRANSLATION.'.json'));
	
	output('<ol>');
	foreach($verses as $verse => $data) {
		$first_line_pali = explode("\\n", $data->text)[0];
		$first_line_english = explode("\\n", $translation_text->$verse)[0];
		output('<li><a href="/dhammapada/gatha/'.$verse.'">'.$first_line_pali.'</a> ⧸ <a href="/dhammapada/gatha/'.$verse.'">'.$first_line_english.'</a></li>');
	}
	output('</ol>');
}


/* Search */

if($uri[0] == 'search') {
	$results = array();
	$query = isset($_REQUEST['query']) ? $_REQUEST['query'] : null;
	if(isset($uri[1]) && $uri[1] !== '') $query = $uri[1];
	
	if($query == '' || $query == ' ') {
		$no_results = true;
		output(null);
	}
	
	// Load verses
	$verses = json_decode(file_get_contents(SITE_PATH.'text/text_dhammapada.json'));
	
	// Load translators
	$translators = json_decode(file_get_contents(SITE_PATH.'text/meta_translators.json'));
	
	// Prepare translations
	foreach($translators as $translator => $data) {
		if(file_exists(SITE_PATH.'text/translation_'.$translator.'.json')) {
			$translations[$translator] = json_decode(file_get_contents(SITE_PATH.'text/translation_'.$translator.'.json'));
		}
	}
	
	// Iterate and check for matches in Pāli text
	foreach($verses as $verse => $data) {
		$query = urldecode($query);
		if(preg_match("/(?<!\pL)$query(?!\pL)/u", $data->text, $matches, PREG_OFFSET_CAPTURE)) {
			$results['pali'][$verse] = 1;
		}
	}
	
	// Iterate and check for matches in translations
	foreach($translations as $translator => $lines) {
		foreach($lines as $line => $text) {
			$query = urldecode($query);
			if(preg_match("/(?<!\pL)$query(?!\pL)/u", $text, $matches, PREG_OFFSET_CAPTURE)) {
				$results['english'][$translator][$line] = 1;
			}
		}
		$lines = array();
	}
	
	output('<form action="/dhammapada/search" method="post">
	<fieldset>
		<legend>Search</legend>
		<p>
			<label for="query">Text to find</label>
			<input type="text" id="query" name="query" value="'.$query.'">
		</p>
		<p>
			<button type="submit"><i class="fas fa-search"></i> Search</button>
		</p>
	</fieldset>
</form>');
	
	if(isset($no_results)) {
		goto end;
	}
	
	if(count($results) == 0) {
		output('<p>There were no matches found for “'.$query.'”.');
	}
	else {
		if(isset($results['pali'])) {
			output('<h2>Pāli</h2>');
			foreach($results['pali'] as $verse => $text) {
				output('<p><strong><a href="/dhammapada/gatha/'.$verse.'">Gatha '.$verse.'</a></strong><br>'.highlight($verses->$verse->text, $query).'</p>');
			}
		}
		
		if(isset($results['english'])) {
			output('<h2>English</h2>');
			foreach($results['english'] as $translator => $data) {
				output('<h3>'.$translators->$translator->name.'</h3>');
				foreach($data as $verse => $null) {
					output('<p><strong><a href="/dhammapada/gatha/'.$verse.'/translation/'.$translator.'">Gatha '.$verse.'</a></strong><br>'.highlight($translations[$translator]->$verse, $query).'</p>');
				}
			}
		}
	}
}

end:

// Default page
if($output == null) {
	header('Location: '.SITE_URL);
	exit;
}
