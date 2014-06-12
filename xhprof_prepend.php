<?php
/*
  On-demand profiling using xhprof.
  This file should be loaded using auto_prepend
  Use by appending a ?profile to the initial URL.
  Disable profiling for all subsequent URLs fetched by appending ?noprofile.
  You need to install and set up xhprof, as described here:
  http://web.archive.org/web/20110514095512/http://mirror.facebook.net/facebook/xhprof/doc.html
*/

// profiler will be enabled only for requests coming from these ip

$allowed_addresses = array(
			   "127.0.0.1",
			   "213.61.228.26"
			   "213.61.153.91"
			   );
// disabled in cli-mode
if (php_sapi_name() != 'cli') {
  if(in_array($_SERVER["REMOTE_ADDR"], $allowed_addresses)) {

    // Do not use const for compatibility with old PHP versions.
    
    // Change this according to where your distribution places
    // xhprof's files, e.g. /usr/share/php52-xhprof/ in Ubuntu for old PHP5.2
    define('XHPROF_ROOT', '/usr/share/php52-xhprof');
    define('XHPROF_DISPLAY', false);
    
    
    if (array_key_exists('profile', $_GET)) {
      setcookie('ThisIsNotAsession','enabled');
      $_COOKIE['ThisIsNotAsession'] = 'enabled';
    } elseif (array_key_exists('noprofile', $_GET)) {
      setcookie('ThisIsNotAsession','disabled');
      $_COOKIE['ThisIsNotAsession'] = 'disabled';
    }
    
    if (!empty($_COOKIE['ThisIsNotAsession']) && $_COOKIE['ThisIsNotAsession'] == 'enabled'
	// Exclude xprof pages from profiling!
	&& strncmp($_SERVER['SCRIPT_NAME'], '/xhprof/', strlen('/xhprof/'))) {
      
      
      // Start profiling.
      // Add XHPROF_FLAGS_NO_BUILTINS to not profile builtin functions.
      xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY,
		    array('ignored_functions' => array('xhprof_disable')));
      
      function xhprof_prepend_finalize() {
	// Stop profiler.
	$xhprof_data = xhprof_disable();
	
	if (XHPROF_DISPLAY) {
	  // Display raw xhprof data for the profiler run.
	  echo '<pre>';
	  var_export($xhprof_data);
	  echo '</pre>';
	} else {
	  // Saving the XHProf run using the default implementation of iXHProfRuns.
	  include_once XHPROF_ROOT . '/xhprof_lib/utils/xhprof_lib.php';
	  include_once XHPROF_ROOT . '/xhprof_lib/utils/xhprof_runs.php';
	  
	  $xhprof_runs = new XHProfRuns_Default();
	  
	  $namespace = isset($_GET['namespace']) ? $_GET['namespace'] : 'xhprof_debug';
	  
	  /*
	    Save the run under a namespace.
	    
	    **NOTE**:
	    By default save_run() will automatically generate a unique
	    run id for you. [You can override that behavior by passing
	    a run id (optional arg) to the save_run() method instead.]
	  */
	  $run_id = $xhprof_runs->save_run($xhprof_data, $namespace);
	  $profile_url = "http://" . $_SERVER['SERVER_NAME'] . "/xhprof/index.php?run=" . $run_id . "&source=" . $namespace;
	  
	  echo '
    <style>
      #xhprof-link {
	position: absolute; 
	top:10px;left:30px;
	display:block;
	font-size:14px;
	font-weight:bold;
	color:#f00;
	border: 1px solid #666;
	background-color: #fff;
	padding:7px 15px;
	}
    </style>';
	  printf('<a id="xhprof-link" href="%s" target="xhprof">View profiler report for this page (id %s) </a>',
		 $profile_url, $run_id);
	}
	
      }
      // BUG: other shutdown functions registered after this won't be profiled.
      register_shutdown_function('xhprof_prepend_finalize');
    }
  }
}
?>
