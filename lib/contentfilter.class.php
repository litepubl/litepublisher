<?php
/**
* Lite Publisher
* Copyright (C) 2010 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tcontentfilter extends tevents {
  
  public static function instance() {
    return getinstance(__class__);
  }
  
  protected function create() {
    parent::create();
    $this->basename = 'contentfilter';
    $this->addevents('oncomment', 'beforecontent', 'aftercontent', 'beforefilter', 'afterfilter');
    $this->data['automore'] = true;
    $this->data['automorelength'] = 250;
    $this->data['phpcode'] = true;
  }
  
  public function filtercomment($content) {
    if ($this->callevent('oncomment', array(&$content))) return $content;
    $result = trim($content);
    $result = htmlspecialchars($result);
    $result = self::simplebbcode($result);
    $result = str_replace(array("\r\n", "\r"), "\n", $result);
    $result = str_replace("\n", "<br />\n", $result);
    return $result;
  }
  
  public function filterpost(tpost $post, $s) {
    $this->callevent('beforecontent', array($post, &$s));
    if ( preg_match('/<!--more(.*?)?-->/', $s, $matches)  ||
    preg_match('/\[more(.*?)?\]/', $s, $matches)  ||
    preg_match('/\[cut(.*?)?\]/', $s, $matches)
    ) {
      $parts = explode($matches[0], $s, 2);
      $post->excerpt = $this->filter($parts[0]);
      $post->filtered = $post->excerpt . '<!--more-->' . $this->ExtractPages($post,$parts[1]);
      $post->rss =  $post->excerpt;
      $post->moretitle =  self::gettitle($matches[1]);
      if ($post->moretitle == '')  $post->moretitle = tlocal::$data['default']['more'];
    } else {
      if ($this->automore) {
        $post->filtered = $this->ExtractPages($post, $s);
        $post->excerpt = self::GetExcerpt($s, $this->automorelength);
        $post->excerpt = $this->filter($post->excerpt);
        $post->rss =  $post->excerpt;
        $post->moretitle = tlocal::$data['default']['more'];
      } else {
        $post->excerpt = $this->ExtractPages($post, $s);
        $post->filtered = $post->excerpt;
        $post->rss =  $post->excerpt;
        $post->moretitle =  '';
      }
    }
    
    $post->description = self::getpostdescription($post->excerpt);
  $this->aftercontent($post);}
  
  public static function getpostdescription($description) {
    if (litepublisher::$options->parsepost) {
      $theme = ttheme::instance();
      $description = $theme->parse($description);
    }
    $description = self::gettitle($description);
    $description = str_replace(
    array("\r", "\n", '  ', '"', "'", '$'),
    array(' ', ' ', ' ', '&quot;', '&#39;', '&#36;'),
    $description);
    $description =str_replace('  ', ' ', $description);
    return $description;
  }
  
  public function ExtractPages(tpost $post, $s) {
    $tag = '<!--nextpage-->';
    $post->deletepages();
    if (!strpos( $s, $tag) )  return $this->filter($s);
    
    while($i = strpos( $s, $tag) ) {
      $page = trim(substr($s, 0, $i));
      $post->addpage($this->filter($page));
      $s = trim(substr($s, $i + strlen($tag)));
    }
    if ($s != '') $post->addpage($this->filter($s));
    return $post->GetPage(0);
  }
  
  public static function gettitle($s) {
    $s = trim($s);
    $s = preg_replace('/\0+/', '', $s);
    $s = preg_replace('/(\\\\0)+/', '', $s);
    $s = strip_tags($s);
    return trim($s);
  }
  
  public function filter($content) {
    if ($this->callevent('beforefilter', array(&$content))) {
      $this->callevent('afterfilter', array(&$content));
      return $content;
    }
    
    $result = trim($content);
    $result = self::auto_p($result);
    $result = $this->replacecode($result);
    $this->callevent('afterfilter', array(&$result));
    return $result;
  }
  
  public function replacecode($s) {
    $s =preg_replace_callback('/<code>(.*?)<\/code>/ims', array(&$this, 'CallbackReplaceCode'), $s);
    if ($this->phpcode) {
      $s = preg_replace_callback('/\<\?(.*?)\?\>/ims', array(&$this, 'callback_replace_php'), $s);
    } else {
      $s = preg_replace_callback('/\<\?(.*?)\?\>/ims', array(&$this, 'callback_fix_php'), $s);
    }
    return $s;
  }
  
  public function callback_fix_php($m) {
    return str_replace("<br />\n", "\n", $m[0]);
  }
  
  public static function specchars($s) {
    $s = str_replace("<br />\n", "\n", $s);
    return str_replace(
    array('"', "'", '$'),
    array('&quot;', '&#39;', '&#36;'),
    htmlspecialchars($s));
  }
  
  public function CallbackReplaceCode($found) {
    return sprintf('<code><pre>%s</pre></code>', self::specchars($found[1]));
  }
  
  public function callback_replace_php($found) {
    return sprintf('<code><pre>%s</pre></code>', self::specchars($found[0]));
  }
  
  public static function getexcerpt($content, $len) {
    $result = strip_tags($content);
    if (strlen($result) <= $len) return $result;
    $chars = "\n ,.;!?:(";
    $p = strlen($result);
    for ($i = strlen($chars) - 1; $i >= 0; $i--) {
      if($pos = strpos($result, $chars[$i], $len)) {
        $p = min($p, $pos + 1);
      }
    }
    return substr($result, 0, $p);
  }
  
  public static function ValidateEmail($email) {
  return  preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $email);
  }
  
  public static function quote($s) {
    return strtr ($s, array('"'=> '&quot;', "'" => '&#039;', '\\'=> '&#092;'));
  }
  
  public static function escape($s) {
    return self::quote(htmlspecialchars(trim(strip_tags($s))));
  }
  
  // uset in tthemeparser
  
  public static function getidtag($tag, $s) {
    if (preg_match("/<$tag\\s*.*?id\\s*=\\s*['\"]([^\"'>]*)/i", $s, $m)) {
      return $m[1];
    }
    return false;
  }
  
  public static function bbcode2tag($s, $code, $tag) {
    if (strpos($s, "[/$code]") !== false) {
      $low = strtolower($s);
      if (substr_count($low, "[$code]") == substr_count($low, "[/$code]")) {
        $s = str_replace("[$code]", "<$tag>", $s);
        $s = str_replace("[/$code]", "</$tag>", $s);
      }
    }
    RETURN $s;
  }
  
  public static function simplebbcode($s){
    $s = self::bbcode2tag($s, 'b', 'cite');
    $s = self::bbcode2tag($s, 'I', 'EM');
    $s = self::bbcode2tag($s, 'code', 'code');
    $s = self::bbcode2tag($s, 'quote', 'blockquote');
    return$s;
  }
  
  public static function auto_p($str) {
    // Trim whitespace
    if (($str = trim($str)) === '') return '';
    
    // Standardize newlines
    $str = str_replace(array("\r\n", "\r"), "\n", $str);
    
    // Trim whitespace on each line
    $str = preg_replace('~^[ \t]+~m', '', $str);
    $str = preg_replace('~[ \t]+$~m', '', $str);
    
    // The following regexes only need to be executed if the string contains html
    if ($html_found = (strpos($str, '<') !== FALSE)) {
      // Elements that should not be surrounded by p tags
      $no_p = '(?:p|div|h[1-6r]|ul|ol|li|blockquote|d[dlt]|pre|t[dhr]|t(?:able|body|foot|head)|c(?:aption|olgroup)|form|s(?:elect|tyle)|a(?:ddress|rea)|ma(?:p|th)|script|code|\?)';
      
      // Put at least two linebreaks before and after $no_p elements
      $str = preg_replace('~^<'.$no_p.'[^>]*+>~im', "\n$0", $str);
      $str = preg_replace('~</'.$no_p.'\s*+>$~im', "$0\n", $str);
    }
    
    // Do the <p> magic!
    $str = '<p>'.trim($str).'</p>';
  $str = preg_replace('~\n{2,}~', "</p>\n\n<p>", $str);
    
    // The following regexes only need to be executed if the string contains html
    if ($html_found !== FALSE) {
      // Remove p tags around $no_p elements
      $str = preg_replace('~<p>(?=</?'.$no_p.'[^>]*+>)~i', '', $str);
      $str = preg_replace('~(</?'.$no_p.'[^>]*+>)</p>~i', '$1', $str);
    }
    
    // Convert single linebreaks to <br />
    $str = preg_replace('~(?<!\n)\n(?!\n)~', "<br />\n", $str);
    
    return $str;
  }
  
}//class
?>