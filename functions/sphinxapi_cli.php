<?php
/**
 * BibleForge (alpha testing)
 *
 * @date    09-23-09
 * @version 0.1 alpha 2
 * @link http://www.BibleForge.com
 */


/// known searchd commands
define('SEARCHD_COMMAND_SEARCH', 0);
define('SEARCHD_COMMAND_EXCERPT', 1);
define('SEARCHD_COMMAND_UPDATE', 2);
define('SEARCHD_COMMAND_KEYWORDS', 3);

/// current client-side command implementation versions
define('VER_COMMAND_SEARCH', 0x113);
define('VER_COMMAND_EXCERPT', 0x100);
define('VER_COMMAND_UPDATE', 0x101);
define('VER_COMMAND_KEYWORDS', 0x100);

/// known searchd status codes
define('SEARCHD_OK', 0);
define('SEARCHD_ERROR', 1);
define('SEARCHD_RETRY', 2);
define('SEARCHD_WARNING', 3);

/// known match modes
define('SPH_MATCH_ALL', 0);
define('SPH_MATCH_ANY', 1);
define('SPH_MATCH_PHRASE', 2);
define('SPH_MATCH_BOOLEAN', 3);
define('SPH_MATCH_EXTENDED', 4);
define('SPH_MATCH_FULLSCAN', 5);
define('SPH_MATCH_EXTENDED2', 6); /// extended engine V2 (TEMPORARY, WILL BE REMOVED)

/// known ranking modes (ext2 only)
define('SPH_RANK_PROXIMITY_BM25', 0); ///< default mode, phrase proximity major factor and BM25 minor one
define('SPH_RANK_BM25', 1); ///< statistical mode, BM25 ranking only (faster but worse quality)
define('SPH_RANK_NONE', 2); ///< no ranking, all matches get a weight of 1
define('SPH_RANK_WORDCOUNT', 3); ///< simple word-count weighting, rank is a weighted sum of per-field keyword occurence counts

/// known sort modes
define('SPH_SORT_RELEVANCE', 0);
define('SPH_SORT_ATTR_DESC', 1);
define('SPH_SORT_ATTR_ASC', 2);
define('SPH_SORT_TIME_SEGMENTS', 3);
define('SPH_SORT_EXTENDED', 4);
define('SPH_SORT_EXPR', 5);

/// known filter types
define('SPH_FILTER_VALUES', 0);
define('SPH_FILTER_RANGE', 1);
define('SPH_FILTER_FLOATRANGE', 2);

/// known attribute types
define('SPH_ATTR_INTEGER', 1);
define('SPH_ATTR_TIMESTAMP', 2);
define('SPH_ATTR_ORDINAL', 3);
define('SPH_ATTR_BOOL', 4);
define('SPH_ATTR_FLOAT', 5);
define('SPH_ATTR_MULTI', 0x40000000);

/// known grouping functions
define('SPH_GROUPBY_DAY', 0);
define('SPH_GROUPBY_WEEK', 1);
define('SPH_GROUPBY_MONTH', 2);
define('SPH_GROUPBY_YEAR', 3);
define('SPH_GROUPBY_ATTR', 4);
define('SPH_GROUPBY_ATTRPAIR', 5);

/// sphinx searchd client class
class SphinxClient
{
	var $_path;			///< search path (default is 'search')
	var $_config;		///< sphinx config file (default is '')
	
	var $_offset;		///< how many records to seek from result-set start (default is 0)
	var $_limit;		///< how many records to return from result-set starting at offset (default is 20)
	var $_mode;			///< query matching mode (default is SPH_MATCH_ALL)
	var $_weights;		///< per-field weights (default is 1 for all fields)
	var $_sort;			///< match sorting mode (default is SPH_SORT_RELEVANCE)
	var $_sortby;		///< attribute to sort by (defualt is "")
	var $_min_id;		///< min ID to match (default is 0, which means no limit)
	var $_max_id;		///< max ID to match (default is 0, which means no limit)
	var $_filters;		///< search filters
	var $_groupby;		///< group-by attribute name
	var $_groupfunc;	///< group-by function (to pre-process group-by attribute value with)
	var $_groupsort;	///< group-by sorting clause (to sort groups in result set with)
	var $_groupdistinct;///< group-by count-distinct attribute
	var $_maxmatches;	///< max matches to retrieve
	var $_cutoff;		///< cutoff to stop searching at (default is 0)
	var $_retrycount;	///< distributed retries count
	var $_retrydelay;	///< distributed retries delay
	var $_anchor;		///< geographical anchor point
	var $_indexweights;	///< per-index weights
	var $_ranker;		///< ranking mode (default is SPH_RANK_PROXIMITY_BM25)
	var $_maxquerytime;	///< max query time, milliseconds (default is 0, do not limit)
	var $_fieldweights;	///< per-field-name weights
	
	var $_error;		///< last error message
	var $_warning;		///< last warning message
	
	var $_reqs;			///< requests array for multi-query
	var $_mbenc;		///< stored mbstring encoding
	var $_arrayresult;	///< whether $result["matches"] should be a hash or an array
	var $_timeout;		///< connect timeout
	
	/// create a new client object and fill defaults
	function SphinxClient ()
	{
		// per-client-object settings
		$this->_path		= 'search';
		$this->_config		= "";
		// per-query settings
		$this->_offset		= 0;
		$this->_limit		= 20;
		$this->_mode		= SPH_MATCH_ALL;
		$this->_weights		= array();
		$this->_sort		= SPH_SORT_RELEVANCE;
		$this->_sortby		= "";
		$this->_min_id		= 0;
		$this->_max_id		= 0;
		$this->_filters		= array();
		$this->_groupby		= "";
		$this->_groupfunc	= SPH_GROUPBY_DAY;
		$this->_groupsort	= '@group desc';
		$this->_groupdistinct= "";
		$this->_maxmatches	= 1000;
		$this->_cutoff		= 0;
		$this->_retrycount	= 0;
		$this->_retrydelay	= 0;
		$this->_anchor		= array();
		$this->_indexweights= array();
		$this->_ranker		= SPH_RANK_PROXIMITY_BM25;
		$this->_maxquerytime= 0;
		$this->_fieldweights= array();
		
		$this->_error		= ""; /// per-reply fields (for single-query case)
		$this->_warning		= "";
		$this->_reqs		= array();	/// requests storage (for multi-query case)
		$this->_mbenc		= "";
		$this->_arrayresult	= false;
		$this->_timeout		= 0;
	}
	
	///NOTE: This is just to be compatible with the default (searchd) api.
	/// set search path (string) and sphinx config file (string)
	function SetServer($path, $config)
	{
		$this->_path = $path;
		$this->_config = $config;
	}
	
	/// set IDs range to match
	/// only match records if document ID is beetwen $min and $max (inclusive)
	function SetIDRange($min, $max)
	{
		$this->_min_id = $min;
		$this->_max_id = $max;
	}
	
	/// set offset and count into result set,
	/// and optionally set max-matches and cutoff limits
	function SetLimits($offset, $limit, $max = 0, $cutoff = 0)
	{
		$this->_offset = $offset;
		$this->_limit = $limit;
		if ($max > 0)
			$this->_maxmatches = $max;
		if ($cutoff > 0)
			$this->_cutoff = $cutoff;
	}
	
	/// set matching mode
	function SetMatchMode($mode)
	{
		$this->_mode = $mode;
	}
	
	/// set matches sorting mode
	function SetSortMode($mode, $sortby = "")
	{
		$this->_sort = $mode;
		$this->_sortby = $sortby;
	}
	
	/// set ranking mode
	function SetRankingMode($ranker)
	{
		$this->_ranker = $ranker;
	}
	
	/// set values set filter
	/// only match records where $attribute value is in given set
	function SetFilter($attribute, $values, $exclude = false)
	{
		if (is_array($values) && count($values)) {
			$this->_filters[] = array('type' => SPH_FILTER_VALUES, 'attr' => $attribute, 'exclude' => $exclude, 'values' => $values);
		}
	}
	
	function Query($query, $index = '*', $comment = "")
	{
		$extra_regex = "";
		
		$options = ' -q';
		$options .= ' -l ' . $this->_limit;
		$options .= ' -s "@id ASC"';
		
		if (isset($this->_filters) && is_array($this->_filters)) {
			foreach ($this->_filters as $values) {
				$options .= ' -f ' . $values['attr'] . ' ' . $values['values'][0];
			}
		}
		
		if ($this->_mode == SPH_MATCH_ANY) {
			$options .= ' -a';
		} elseif ($this->_mode == SPH_MATCH_PHRASE) {
			$options .= ' -p';
		} elseif ($this->_mode == SPH_MATCH_BOOLEAN) {
			$options .= ' -b';
		} elseif ($this->_mode == SPH_MATCH_EXTENDED) {
			$options .= ' -e'; ///NOTE: It may be better to use -e2.
		} elseif ($this->_mode == SPH_MATCH_EXTENDED2) {
			$options .= ' -e2';
		}
		
		if ($this->_min_id > 0 || $this->_max_id > 0) {
			$sortexpr = ' -S "';
			if ($this->_min_id > 0) {
				$sortexpr .= '@id >= ' . $this->_min_id;
			}
			if ($this->_max_id > 0) {
				if ($this->_min_id > 0) {
					$sortexpr .= ' AND ';
				}
				$sortexpr .= '@id <= ' . $this->_max_id;
			}
			$options .= $sortexpr . '"';
			$extra_regex = ', @expr=1';
			
		}
		
		///TODO: Does this work in Linux?
		$cmd = $this->_path . $options . ' -c ' . $this->_config . ' -i ' . $index . ' "' . str_replace('"', '\"', $query) . '"';
		
		$res = shell_exec($cmd);
		
		preg_match_all('/^\d+\. \'([^\']+)\': (\d+) documents, (\d+)/im', $res, $hits);
		preg_match('/: returned (\d+) matches of (\d+) total in ([0-9.]+)/i', $res, $stats);
		preg_match_all('/ document=.*' . $extra_regex . '/', $res, $matches);
		$matches = preg_filter('/ ([^=]+)=/i', '"\1":', $matches[0]);
		$mathces_attrs = array();
		foreach($matches as $value) {
			$tmp_arr = json_decode('{' . $value . '}', true);
			$doc = $tmp_arr['document'];
			$mathces_attrs[$doc]['weight'] = $tmp_arr['weight'];
			unset($tmp_arr['document']);
			unset($tmp_arr['weight']);
			$mathces_attrs[$doc]['attrs'] = $tmp_arr;
		}
		$hits_ret = array();
		foreach ($hits[1] as $key => $value) {
			$hits_ret[$value] = array('docs' => $hits[2][$key], 'hits' => $hits[3][$key]);
		}
		
		return array('error' => "", 'warning' => "", 'matches' => $mathces_attrs, 'total' => $stats[1], 'total_found' => $stats[2], 'time' => $stats[3], 'words' => $hits_ret);
	}
}