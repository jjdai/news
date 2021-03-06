<?php
// $Id: index.php 9767 2012-07-02 06:02:52Z beckmi $
//  ------------------------------------------------------------------------ //
//                XOOPS - PHP Content Management System                      //
//                    Copyright (c) 2000 XOOPS.org                           //
//                       <http://www.xoops.org/>                             //
// ------------------------------------------------------------------------- //
//  This program is free software; you can redistribute it and/or modify     //
//  it under the terms of the GNU General Public License as published by     //
//  the Free Software Foundation; either version 2 of the License, or        //
//  (at your option) any later version.                                      //
//                                                                           //
//  You may not change or alter any portion of this comment or credits       //
//  of supporting developers from this source code or any supporting         //
//  source code which is considered copyrighted (c) material of the          //
//  original comment or credit authors.                                      //
//                                                                           //
//  This program is distributed in the hope that it will be useful,          //
//  but WITHOUT ANY WARRANTY; without even the implied warranty of           //
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            //
//  GNU General Public License for more details.                             //
//                                                                           //
//  You should have received a copy of the GNU General Public License        //
//  along with this program; if not, write to the Free Software              //
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA //
//  ------------------------------------------------------------------------ //
/**
 * Module's index
 *
 * This page displays a list of the published articles and can also display the
 * stories of a particular topic.
 *
 * @package News
 * @author Xoops Modules Dev Team
 * @copyright (c) The Xoops Project - www.xoops.org
 *
 * Parameters received by this page :
 * @page_param 	int		storytopic 					Topic's ID
 * @page_param	int		topic_id					Topic's ID
 * @page_param	int		storynum					Number of news per page
 * @page_param	int		start						First news to display
 *
 * @page_title			Topic's title - Story's title - Module's name
 *
 * @template_name		news_index.html or news_by_topic.html
 *
 * Template's variables :
 * For each article
 * @template_var 	int		id			story's ID
 * @template_var 	string	poster		Complete link to the author's profile
 * @template_var 	string	author_name	Author's name according to the module's option called displayname
 * @template_var 	int		author_uid	Author's ID
 * @template_var 	float	rating		New's rating
 * @template_var 	int		votes		number of votes
 * @template_var 	int		posttimestamp Timestamp representing the published date
 * @template_var 	string	posttime		Formated published date
 * @template_var 	string	text		The introduction's text
 * @template_var 	string	morelink	The link to read the full article (points to article.php)
 * @template_var 	string	adminlink	Link reserved to the admin to edit and delete the news
 * @template_var 	string	mail_link	Link used to send the story's url by email
 * @template_var 	string	title		Story's title presented on the form of a link
 * @template_var	string	news_title	Just the news title
 * @template_var	string	topic_title	Just the topic's title
 * @template_var	int		hits		Number of times the article was read
 * @template_var 	int		files_attached	Number of files attached to this news
 * @template_var 	string	attached_link	An URL pointing to the attached files
 * @template_var 	string	topic_color	The topic's color
 * @template_var 	int		columnwidth	column's width
 * @template_var 	int		displaynav	To know if we must display the navigation's box
 * @template_var 	string	lang_go		fixed text : Go!
 * @template_var 	string	lang_morereleases	fixed text : More releases in
 * @template_var 	string	lang_on		fixed text : on
 * @template_var 	string	lang_postedby	fixed text : Posted by
 * @template_var 	string	lang_printerpage	fixed text : Printer Friendly Page
 * @template_var 	string	lang_ratethisnews	fixed text : Rate this News
 * @template_var 	string	lang_ratingc	fixed text : Rating:
 * @template_var 	string	lang_reads		fixed text : reads
 * @template_var 	string	lang_sendstory	fixed text : Send this Story to a Friend
 * @template_var 	string	 topic_select	contains the topics selector
*/
include_once '../../mainfile.php';
include_once XOOPS_ROOT_PATH.'/modules/news/class/class.newsstory.php';
include_once XOOPS_ROOT_PATH.'/modules/news/class/class.sfiles.php';
include_once XOOPS_ROOT_PATH.'/modules/news/class/class.newstopic.php';
include_once XOOPS_ROOT_PATH.'/modules/news/include/functions.php';
include_once XOOPS_ROOT_PATH.'/class/tree.php';

$storytopic=0;
if(isset($_GET['storytopic'])) {
	$storytopic=intval($_GET['storytopic']);
} else {
	if(isset($_GET['topic_id'])) {
		$storytopic=intval($_GET['topic_id']);
	}
}

if ($storytopic) {
    $groups = is_object($xoopsUser) ? $xoopsUser->getGroups() : XOOPS_GROUP_ANONYMOUS;
    $gperm_handler =& xoops_gethandler('groupperm');
    if (!$gperm_handler->checkRight('news_view', $storytopic, $groups, $xoopsModule->getVar('mid'))) {
        redirect_header(XOOPS_URL.'/modules/news/index.php', 3, _NOPERM);
        exit();
    }
	$xoopsOption['storytopic'] = $storytopic;
} else {
	$xoopsOption['storytopic'] = 0;
}
if (isset($_GET['storynum'])) {
	$xoopsOption['storynum'] = intval($_GET['storynum']);
	if ($xoopsOption['storynum'] > 30) {
		$xoopsOption['storynum'] = $xoopsModuleConfig['storyhome'];
	}
} else {
	$xoopsOption['storynum'] = $xoopsModuleConfig['storyhome'];
}

if (isset($_GET['start']) ) {
	$start = intval($_GET['start']);
} else {
	$start = 0;
}

if (empty($xoopsModuleConfig['newsdisplay']) || $xoopsModuleConfig['newsdisplay'] == 'Classic' || $xoopsOption['storytopic'] > 0) {
    $showclassic = 1;
} else {
    $showclassic = 0;
}
$firsttitle='';
$topictitle='';
$myts =& MyTextSanitizer::getInstance();
$sfiles = new sFiles();

$column_count = $xoopsModuleConfig['columnmode'];

if ($showclassic) {
    $xoopsOption['template_main'] = 'news_index.html';
	include_once XOOPS_ROOT_PATH.'/header.php';
	$xt = new NewsTopic();

    $xoopsTpl->assign('columnwidth', intval(1/$column_count*100));
	if ($xoopsModuleConfig['ratenews']) {
		$xoopsTpl->assign('rates', true);
		$xoopsTpl->assign('lang_ratingc', _NW_RATINGC);
		$xoopsTpl->assign('lang_ratethisnews', _NW_RATETHISNEWS);
	} else {
		$xoopsTpl->assign('rates', false);
	}

	if($xoopsOption['storytopic']) {
		$xt->getTopic($xoopsOption['storytopic']);
		$xoopsTpl->assign('topic_description', $xt->topic_description('S'));
		$xoopsTpl->assign('topic_color', '#'.$xt->topic_color('S'));
		$topictitle=$xt->topic_title();
	}

	if ($xoopsModuleConfig['displaynav'] == 1 ) {
        $xoopsTpl->assign('displaynav', true);

		$allTopics = $xt->getAllTopics($xoopsModuleConfig['restrictindex']);
		$topic_tree = new XoopsObjectTree($allTopics, 'topic_id', 'topic_pid');
		$topic_select = $topic_tree->makeSelBox('storytopic', 'topic_title', '-- ', $xoopsOption['storytopic'], true);

        $xoopsTpl->assign('topic_select', $topic_select);
        $storynum_options = '';
        for ( $i = 5; $i <= 30; $i = $i + 5 ) {
            $sel = '';
            if ($i == $xoopsOption['storynum']) {
                $sel = ' selected="selected"';
            }
            $storynum_options .= '<option value="'.$i.'"'.$sel.'>'.$i.'</option>';
        }
        $xoopsTpl->assign('storynum_options', $storynum_options);
    } else {
        $xoopsTpl->assign('displaynav', false);
    }
	if($xoopsOption['storytopic']==0) {
		$topic_frontpage=true;
	} else {
		$topic_frontpage=false;
	}
	$sarray = NewsStory::getAllPublished($xoopsOption['storynum'], $start, $xoopsModuleConfig['restrictindex'], $xoopsOption['storytopic'], 0, true, 'published', $topic_frontpage);

    $scount = count($sarray);
    $xoopsTpl->assign('story_count', $scount);
    $k = 0;
    $columns = array();
    if($scount>0)
    {
    	$storieslist=array();
    	foreach ($sarray as $storyid => $thisstory) {
    		$storieslist[]=$thisstory->storyid();
    	}
		$filesperstory = $sfiles->getCountbyStories($storieslist);

	    foreach ($sarray as $storyid => $thisstory) {
	    	$filescount = array_key_exists($thisstory->storyid(),$filesperstory) ? $filesperstory[$thisstory->storyid()] : 0;
        	$story = $thisstory->prepare2show($filescount);
        	// The line below can be used to display a Permanent Link image
        	// $story['title'] .= "&nbsp;&nbsp;<a href='".XOOPS_URL."/modules/news/article.php?storyid=".$sarray[$i]->storyid()."'><img src='".XOOPS_URL."/modules/news/images/x.gif' alt='Permanent Link' /></a>";
        	$story['news_title'] = $story['title'];
        	$story['title'] = $thisstory->textlink().'&nbsp;:&nbsp;'.$story['title'];
        	$story['topic_title'] = $thisstory->textlink();
        	$story['topic_color'] = '#'.$myts->displayTarea($thisstory->topic_color);
	       	if($firsttitle=='') {
       			$firsttitle=$thisstory->topic_title() . ' - ' .  $thisstory->title();
       		}
        	$columns[$k][] = $story;
        	$k++;
        	if ($k == $column_count) {
	            $k = 0;
        	}
		}
	}
	$xoopsTpl->assign('columns', $columns);
	unset($story);

	$totalcount = NewsStory::countPublishedByTopic($xoopsOption['storytopic'], $xoopsModuleConfig['restrictindex']);
    if ( $totalcount > $scount ) {
        include_once XOOPS_ROOT_PATH.'/class/pagenav.php';
		$pagenav = new XoopsPageNav($totalcount, $xoopsOption['storynum'], $start, 'start', 'storytopic='.$xoopsOption['storytopic']);
		if(news_isbot()) { 		// A bot is reading the news, we are going to show it all the links so that he can read everything
        	$xoopsTpl->assign('pagenav', $pagenav->renderNav($totalcount));
        } else {
            $xoopsTpl->assign('pagenav', $pagenav->renderNav());
    	}
    } else {
        $xoopsTpl->assign('pagenav', '');
    }
} else {	// Affichage par sujets
    $xoopsOption['template_main'] = 'news_by_topic.html';
    include_once XOOPS_ROOT_PATH.'/header.php';
    $xoopsTpl->assign('columnwidth', intval(1/$column_count*100));
	if ($xoopsModuleConfig['ratenews']) {
		$xoopsTpl->assign('rates', true);
		$xoopsTpl->assign('lang_ratingc', _NW_RATINGC);
		$xoopsTpl->assign('lang_ratethisnews', _NW_RATETHISNEWS);
	} else {
		$xoopsTpl->assign('rates', false);
	}

	$xt = new NewsTopic();
    $alltopics =& $xt->getTopicsList(true,$xoopsModuleConfig['restrictindex']);
    $smarty_topics = array();
    $topicstories = array();

    foreach ($alltopics as $topicid => $topic) {
		$allstories = NewsStory::getAllPublished($xoopsModuleConfig['storyhome'], 0, $xoopsModuleConfig['restrictindex'], $topicid);
    	$storieslist=array();
    	foreach ($allstories as $thisstory) {
    		$storieslist[]=$thisstory->storyid();
    	}
		$filesperstory = $sfiles->getCountbyStories($storieslist);
		foreach ($allstories as $thisstory) {
			$filescount = array_key_exists($thisstory->storyid(),$filesperstory) ? $filesperstory[$thisstory->storyid()] : 0;
			$story = $thisstory->prepare2show($filescount);
			$story['topic_title'] = $thisstory->textlink();
			$story['news_title'] = $story['title'];
			$topicstories[$topicid][] = $story;
		}
		if(isset($topicstories[$topicid])) {
			$smarty_topics[$topicstories[$topicid][0]['posttimestamp']] = array('title' => $topic['title'], 'stories' => $topicstories[$topicid], 'id' => $topicid, 'topic_color'=>$topic['color']);
		}
    }

    krsort($smarty_topics);
    $columns = array();
    $i = 0;
    foreach ($smarty_topics as $thistopictimestamp => $thistopic) {
        $columns[$i][] = $thistopic;
        $i++;
        if ($i == $column_count) {
            $i = 0;
        }
    }
    //$xoopsTpl->assign('topics', $smarty_topics);
    $xoopsTpl->assign('columns', $columns);
}

$xoopsTpl->assign('advertisement', news_getmoduleoption('advertisement'));

/**
 * Create the Meta Datas
 */
news_CreateMetaDatas();


/**
 * Create a clickable path from the root to the current topic (if we are viewing a topic)
 * Actually this is not used in the default templates but you can use it as you want
 * You can comment the code to optimize the requests count
 */
if($xoopsOption['storytopic']) {
	include_once XOOPS_ROOT_PATH.'/class/xoopstree.php';
	$mytree = new XoopsTree($xoopsDB->prefix('topics'),'topic_id','topic_pid');
	$topicpath = $mytree->getNicePathFromId($xoopsOption['storytopic'], 'topic_title', 'index.php?op=1');
	$xoopsTpl->assign('topic_path', $topicpath);
	unset($mytree);
}

/**
 * Create a link for the RSS feed (if the module's option is activated)
 */
$moduleInfo =& $module_handler->get($xoopsModule->getVar('mid')); 
if($xoopsModuleConfig['topicsrss'] && $xoopsOption['storytopic']) {
	$link=sprintf("<a href='%s' title='%s'><img src='%s' border='0' alt='%s'></a>",XOOPS_URL.'/modules/news/backendt.php?topicid='.$xoopsOption['storytopic'], _NW_RSSFEED, XOOPS_URL ."/". $moduleInfo->getInfo('icons16').'/rss.gif',_NW_RSSFEED);
	$xoopsTpl->assign('topic_rssfeed_link',$link);
}

/**
 * Assign page's title
 */
if($firsttitle!='') {
	$xoopsTpl->assign('xoops_pagetitle', $firsttitle . ' - ' . $xoopsModule->name('s'));
} else {
	if($topictitle!='') {
		$xoopsTpl->assign('xoops_pagetitle', $topictitle);
	} else {
		$xoopsTpl->assign('xoops_pagetitle', $xoopsModule->name('s'));
	}
}

$xoopsTpl->assign('lang_go', _GO);
$xoopsTpl->assign('lang_on', _ON);
$xoopsTpl->assign('lang_printerpage', _NW_PRINTERFRIENDLY);
$xoopsTpl->assign('lang_sendstory', _NW_SENDSTORY);
$xoopsTpl->assign('lang_postedby', _POSTEDBY);
$xoopsTpl->assign('lang_reads', _READS);
$xoopsTpl->assign('lang_morereleases', _NW_MORERELEASES);
include_once XOOPS_ROOT_PATH.'/footer.php';
?>