<?php
/**********************************************************
New 2010 forums that don't suck for TB based sites....
pretty much coded page by page, but coming from a 
history ot TBsourse and TBDev and the many many 
coders who helped develop them over time.
proper credits to follow :)

beta sun aug 1st 2010 v0.1
view forum

Powered by Bunnies!!!
 
 ***************************************************************/
if (!defined('BUNNY_FORUMS')) {
    $HTMLOUT = '';
    $HTMLOUT.= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
        <head>
        <meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
        <title>ERROR</title>
        </head><body>
        <h1 style="text-align:center;">ERROR</h1>
        <p style="text-align:center;">How did you get here? silly rabbit Trix are for kids!.</p>
        </body></html>';
    echo $HTMLOUT;
    exit();
}
$posts = $colour = $delete_me = $rpic = $content = $child = $parent_forum_name = $first_post_arr = $post_status_image = '';
$forum_id = (isset($_GET['forum_id']) ? intval($_GET['forum_id']) : (isset($_POST['forum_id']) ? intval($_POST['forum_id']) : 0));
if (!is_valid_id($forum_id)) {
    stderr('Error', 'Bad ID.');
}
//=== who is here
sql_query('DELETE FROM now_viewing WHERE user_id = ' . sqlesc($CURUSER['id']));
sql_query('INSERT INTO now_viewing (user_id, forum_id, added) VALUES(' . sqlesc($CURUSER['id']) . ', ' . sqlesc($forum_id) . ', ' . TIME_NOW . ')');
//=== Get forum data
$res = sql_query('SELECT name, min_class_read, min_class_write, min_class_create, forum_id, parent_forum FROM forums WHERE min_class_read <= ' . sqlesc($CURUSER['class']) . ' AND id=' . sqlesc($forum_id) . ' LIMIT 1');
$arr = mysqli_fetch_assoc($res);
$forum_name = htmlsafechars($arr['name'], ENT_QUOTES);
$parent_forum_id = (int)$arr['parent_forum'];
if ($CURUSER['class'] < $arr['min_class_read']) {
    stderr('Error', 'Bad ID.'); //=== why tell them there is a forums here...
    
}
$may_post = ($CURUSER['class'] >= $arr['min_class_write'] && $CURUSER['class'] >= $arr['min_class_create'] && $CURUSER['forum_post'] == 'yes' && $CURUSER['suspended'] == 'no');
//=== if a sub forum, get the info!
$res_sub_forums = sql_query('SELECT id AS sub_forum_id, name AS sub_form_name, description AS sub_form_description, min_class_read, post_count AS sub_form_post_count, topic_count AS sub_form_topic_count FROM forums WHERE min_class_read <= ' . sqlesc($CURUSER['class']) . ' AND parent_forum=' . sqlesc($forum_id) . ' ORDER BY sort');
if ($res_sub_forums) {
    //===sub forums
    $sub_forums_stuff = '';
    while ($sub_forums_arr = mysqli_fetch_array($res_sub_forums)) {
        //=== change colors
        $colour = (++$colour) % 2;
        $class = ($colour == 0 ? 'one' : 'two');
        if ($CURUSER['class'] < $sub_forums_arr['min_class_read']) die;
        $post_res = sql_query('SELECT t.id AS topic_id, t.topic_name, t.status AS topic_status, t.anonymous AS tan, 
												p.id AS last_post_id, p.topic_id, p.added, p.anonymous AS pan,
												u.id, u.username, u.class, u.donor, u.suspended, u.warned, u.enabled, u.chatpost, u.leechwarn, u.pirate, u.king
												FROM topics AS t 
												LEFT JOIN posts AS p ON t.id = p.topic_id 
												LEFT JOIN users AS u ON p.user_id = u.id 
												WHERE ' . ($CURUSER['class'] < UC_STAFF ? ' p.status = \'ok\' AND t.status = \'ok\' AND' : ($CURUSER['class'] < $min_delete_view_class ? ' p.status != \'deleted\'  AND t.status != \'deleted\'  AND' : '')) . ' 
												t.forum_id=' . sqlesc($sub_forums_arr['sub_forum_id']) . ' ORDER BY p.id DESC LIMIT 1');
        $post_arr = mysqli_fetch_assoc($post_res);
        //=== only do more if there is a post there...
        if ($post_arr['last_post_id'] > 0) {
            $last_topic_id = (int)$post_arr['topic_id'];
            $last_post_id = (int)$post_arr['last_post_id'];
            //=== topic status
            $topic_status = htmlsafechars($post_arr['topic_status']);
            switch ($topic_status) {
            case 'ok':
                $topic_status_image = '';
                break;

            case 'recycled':
                $topic_status_image = ' <img src="' . $INSTALLER09['pic_base_url'] . 'forums/recycle_bin.gif" alt="Recycled" title="This topic is currently in the recycle-bin" />';
                break;

            case 'deleted':
                $topic_status_image = ' <img src="' . $INSTALLER09['pic_base_url'] . 'forums/delete_icon.gif" alt="Deleted" title="This topic is currently deleted" />';
                break;
            }
            //== Anonymous
            if ($post_arr["tan"] == "yes") {
                if ($CURUSER['class'] < UC_STAFF && $post_arr["user_id"] != $CURUSER["id"]) $last_post = '<span style="white-space:nowrap;">Last Post by: <i>Anonymous</i> in &#9658; <a class="altlink" href="' . $INSTALLER09['baseurl'] . '/forums.php?action=view_topic&amp;topic_id=' . $last_topic_id . '&amp;page=' . $last_post_id . '#' . $last_post_id . '" title="' . htmlsafechars($post_arr['topic_name'], ENT_QUOTES) . '">
						<span style="font-weight: bold;">' . CutName(htmlsafechars($post_arr['topic_name'], ENT_QUOTES) , 30) . '</span></a>' . $topic_status_image . '<br />
						' . get_date($post_arr['added'], '') . '<br /></span>';
                else $last_post = '<span style="white-space:nowrap;">Last Post by: <i>Anonymous</i> [' . print_user_stuff($post_arr) . '] 
						<span style="font-size: x-small;"> [ ' . get_user_class_name($post_arr['class']) . ' ] </span><br />
						in &#9658; <a class="altlink" href="' . $INSTALLER09['baseurl'] . '/forums.php?action=view_topic&amp;topic_id=' . $last_topic_id . '&amp;page=' . $last_post_id . '#' . $last_post_id . '" title="' . htmlsafechars($post_arr['topic_name'], ENT_QUOTES) . '">
						<span style="font-weight: bold;">' . CutName(htmlsafechars($post_arr['topic_name'], ENT_QUOTES) , 30) . '</span></a>' . $topic_status_image . '<br />
						' . get_date($post_arr['added'], '') . '<br /></span>';
            } else {
                $last_post = '<span style="white-space:nowrap;">Last Post by: ' . print_user_stuff($post_arr) . ' 
						<span style="font-size: x-small;"> [ ' . get_user_class_name($post_arr['class']) . ' ] </span><br />
						in &#9658; <a class="altlink" href="' . $INSTALLER09['baseurl'] . '/forums.php?action=view_topic&amp;topic_id=' . $last_topic_id . '&amp;page=' . $last_post_id . '#' . $last_post_id . '" title="' . htmlsafechars($post_arr['topic_name'], ENT_QUOTES) . '">
						<span style="font-weight: bold;">' . CutName(htmlsafechars($post_arr['topic_name'], ENT_QUOTES) , 30) . '</span></a>' . $topic_status_image . '<br />
						' . get_date($post_arr['added'], '') . '<br /></span>';
            }
            //=== last post read in topic
            $last_unread_post_res = sql_query('SELECT last_post_read FROM read_posts WHERE user_id=' . sqlesc($CURUSER['id']) . ' AND topic_id=' . sqlesc($last_post_id));
            $last_unread_post_arr = mysqli_fetch_row($last_unread_post_res);
            $last_unread_post_id = ($last_unread_post_arr[0] >= 0 ? $last_unread_post_arr[0] : $first_post_arr['first_post_id']);
            $image_to_use = ($post_arr['added'] > (TIME_NOW - $readpost_expiry)) ? (!$last_unread_post_arr || $last_post_id > $last_unread_post_arr[0]) : 0;
            $img = ($image_to_use ? 'unlockednew' : 'unlocked');
        } else {
            $last_post = 'N/A';
            $img = 'unlocked';
        }
        $sub_forums_stuff.= '<tr>
								<td align="left" class="' . $class . '"><table border="0" cellspacing="0" cellpadding="0">
								<tr>
								<td class="' . $class . '" style="padding-right: 5px"><img src="' . $INSTALLER09['pic_base_url'] . 'forums/' . $img . '.gif" alt="' . $img . '" title="' . $img . '" /></td>
								<td class="' . $class . '"><a class="altlink" href="?action=view_forum&amp;forum_id=' . (int)$sub_forums_arr['sub_forum_id'] . '">
								' . htmlsafechars($sub_forums_arr['sub_form_name'], ENT_QUOTES) . '</a>
								' . ($CURUSER['class'] >= UC_ADMINISTRATOR ? '<span style="font-size: x-small;"> 
								[<a class="altlink" href="staffpanel.php?tool=forum_manage&amp;action=forum_manage&amp;action2=edit_forum_page&amp;id=' . (int)$sub_forums_arr['sub_forum_id'] . '">Edit</a>] 
								[<a class="altlink" href="' . $INSTALLER09['baseurl'] . '/forums.php?action=delete_forum&amp;forum_id=' . (int)$sub_forums_arr['sub_forum_id'] . '">Delete</a>]
								</span>' : '') . '<br />
								<span style="font-size: x-small;">' . htmlsafechars($sub_forums_arr['sub_form_description'], ENT_QUOTES) . '</span></td>
								</tr>
								</table>
								</td>
								<td class="' . $class . '" align="center" width="100px"><span style="font-size: x-small;"> 
								' . number_format($sub_forums_arr['sub_form_post_count']) . ' Posts<br />
								' . number_format($sub_forums_arr['sub_form_topic_count']) . ' Topics</span></td>
								<td class="' . $class . '" align="left" width="200px"><span style="font-size: x-small;"> ' . $last_post . ' </span></td>
								</tr>';
    } //=== end while loop
    $sub_forums = ($sub_forums_stuff !== '' ? '<table border="0" cellspacing="0" cellpadding="5" width="90%">
					<tr><td class="forum_head_dark" align="left" colspan="3">' . $forum_name . ' Child Boards</td>
					</tr>' . $sub_forums_stuff . '</table>' : '');
    //=== now we need the parent forums name :P I'll try to get this into another query :P
    $parent_forum_res = sql_query('SELECT name AS parent_forum_name FROM forums WHERE id=' . sqlesc($parent_forum_id) . ' LIMIT 1');
    $parent_forum_arr = mysqli_fetch_assoc($parent_forum_res);
    if ($arr['parent_forum'] > 0) {
        $child = '<span style="font-size: x-small;"> [ child-board ]</span>';
        $parent_forum_name = '<img src="' . $INSTALLER09['pic_base_url'] . 'arrow_next.gif" alt="&#9658;" title="&#9658;" /> 
			<a class="altlink" href="' . $INSTALLER09['baseurl'] . '/forums.php?action=view_forum&amp;forum_id=' . $parent_forum_id . '">' . htmlsafechars($parent_forum_arr['parent_forum_name'], ENT_QUOTES) . '</a>';
    }
}
//=== Get topic count
$res = sql_query('SELECT COUNT(id) FROM topics 	WHERE  ' . ($CURUSER['class'] < UC_STAFF ? ' status = \'ok\' AND' : ($CURUSER['class'] < $min_delete_view_class ? ' status != \'deleted\'  AND' : '')) . '  forum_id=' . sqlesc($forum_id));
$row = mysqli_fetch_row($res);
$count = $row[0];
//=== get stuff for the pager
$page = isset($_GET['page']) ? (int)$_GET['page'] : 0;
$perpage = $CURUSER['topicsperpage'] !== 0 ? $CURUSER['topicsperpage'] : (isset($_GET['perpage']) ? (int)$_GET['perpage'] : 15);
//$perpage = max(($CURUSER['topicsperpage'] !== 0 ? $CURUSER['topicsperpage'] :  (isset($_GET['perpage']) ? (int)$_GET['perpage'] : 15)), 15);
list($menu, $LIMIT) = pager_new($count, $perpage, $page, 'forums.php?action=view_forum&amp;forum_id=' . $forum_id . (isset($_GET['perpage']) ? '&amp;perpage=' . $perpage : ''));
//=== Get topics data
$topic_res = sql_query('SELECT * FROM topics WHERE  ' . ($CURUSER['class'] < UC_STAFF ? ' status = \'ok\' AND' : ($CURUSER['class'] < $min_delete_view_class ? ' status != \'deleted\'  AND' : '')) . '  forum_id=' . $forum_id . ' ORDER BY sticky, last_post DESC ' . $LIMIT);
$location_bar = '<h1><a class="altlink" href="index.php">' . $INSTALLER09['site_name'] . '</a>  <img src="' . $INSTALLER09['pic_base_url'] . 'arrow_next.gif" alt="&#9658;" title="&#9658;" /> 
			<a class="altlink" href="' . $INSTALLER09['baseurl'] . '/forums.php">Forums</a> ' . $parent_forum_name . ' <img src="' . $INSTALLER09['pic_base_url'] . 'arrow_next.gif" alt="&#9658;" title="&#9658;" />
			<a class="altlink" href="' . $INSTALLER09['baseurl'] . '/forums.php?action=view_forum&amp;forum_id=' . $forum_id . '">' . $forum_name . $child . '</a></h1>
			' . $mini_menu . '
			<br /><br />';
if ($count > 0) {
    while ($topic_arr = mysqli_fetch_assoc($topic_res)) {
        $topic_id = (int)$topic_arr['id'];
        $locked = $topic_arr['locked'] == 'yes';
        $sticky = $topic_arr['sticky'] == 'yes';
        $topic_poll = (int)$topic_arr['poll_id'] > 0;
        //=== topic status
        $topic_status = htmlsafechars($topic_arr['status']);
        switch ($topic_status) {
        case 'ok':
            $topic_status_image = '';
            break;

        case 'recycled':
            $topic_status_image = ' <img src="' . $INSTALLER09['pic_base_url'] . 'forums/recycle_bin.gif" alt="Recycled" title="This topic is currently in the recycle-bin" />';
            break;

        case 'deleted':
            $topic_status_image = ' <img src="' . $INSTALLER09['pic_base_url'] . 'forums/delete_icon.gif" alt="Deleted" title="This topic is currently deleted" />';
            break;
        }
        //=== Get user ID and date of last post
        $res_post_stuff = sql_query('SELECT p.id AS last_post_id, p.added, p.user_id,  p.status, p.anonymous,
												u.id, u.username, u.class, u.donor, u.suspended, u.warned, u.enabled, u.chatpost, u.leechwarn, u.pirate, u.king
												FROM posts AS p 
												LEFT JOIN users AS u ON p.user_id = u.id 
												WHERE  ' . ($CURUSER['class'] < UC_STAFF ? ' p.status = \'ok\' AND' : ($CURUSER['class'] < $min_delete_view_class ? ' p.status != \'deleted\'  AND' : '')) . '  topic_id=' . sqlesc($topic_id) . '
												ORDER BY p.id DESC LIMIT 1');
        $arr_post_stuff = mysqli_fetch_assoc($res_post_stuff);
        //=== post status
        $post_status = htmlsafechars($arr_post_stuff['status']);
        switch ($post_status) {
        case 'ok':
            $post_status_image = '';
            break;

        case 'recycled':
            $post_status_image = ' <img src="' . $INSTALLER09['pic_base_url'] . 'forums/recycle_bin.gif" alt="Recycled" title="This post is currently in the recycle-bin" width="18px" />';
            break;

        case 'deleted':
            $post_status_image = ' <img src="' . $INSTALLER09['pic_base_url'] . 'forums/delete_icon.gif" alt="Deleted" title="This post is currently deleted" width="18px" />';
            break;

        case 'postlocked':
            $post_status = 'postlocked';
            $post_status_image = ' <img src="' . $INSTALLER09['pic_base_url'] . 'forums/thread_locked.gif" alt="Locked" title="This post is currently locked" width="18px" />';
            break;
        }
        //== Anonymous
        if ($arr_post_stuff['anonymous'] == 'yes') {
            if ($CURUSER['class'] < UC_STAFF && $arr_post_stuff['user_id'] != $CURUSER['id']) $last_post_username = ($arr_post_stuff['username'] !== '' ? '<i>Anonymous</i>' : 'Lost [' . (int)$arr_post_stuff['id'] . ']');
            else $last_post_username = ($arr_post_stuff['username'] !== '' ? '<i>Anonymous</i> [' . print_user_stuff($arr_post_stuff) . ']' : 'Lost [' . (int)$arr_post_stuff['id'] . ']');
        } else {
            $last_post_username = ($arr_post_stuff['username'] !== '' ? print_user_stuff($arr_post_stuff) : 'Lost [' . (int)$arr_post_stuff['id'] . ']');
        }
        //==
        $last_post_id = (int)$arr_post_stuff['last_post_id'];
        //=== Get author / first post info
        $first_post_res = sql_query('SELECT p.id AS first_post_id, p.added, p.icon, p.body, p.anonymous, p.user_id,
												u.id, u.username, u.class, u.donor, u.suspended, u.warned, u.enabled, u.chatpost, u.leechwarn, u.pirate, u.king
												FROM posts AS p 
												LEFT JOIN users AS u ON p.user_id = u.id 
												WHERE  ' . ($CURUSER['class'] < UC_STAFF ? ' p.status = \'ok\' AND' : ($CURUSER['class'] < $min_delete_view_class ? ' p.status != \'deleted\'  AND' : '')) . '  
												topic_id=' . sqlesc($topic_id) . ' ORDER BY p.id ASC LIMIT 1');
        $first_post_arr = mysqli_fetch_assoc($first_post_res);
        //== Anonymous
        if ($first_post_arr['anonymous'] == 'yes') {
            if ($CURUSER['class'] < UC_STAFF && $first_post_arr['user_id'] != $CURUSER['id']) $thread_starter = ($first_post_arr['username'] !== '' ? '<i>Anonymous</i>' : 'Lost [' . $topic_arr['user_id'] . ']') . '<br />' . get_date($first_post_arr['added'], '');
            else $thread_starter = ($first_post_arr['username'] !== '' ? '<i>Anonymous</i> [' . print_user_stuff($first_post_arr) . ']' : 'Lost [' . $topic_arr['user_id'] . ']') . '<br />' . get_date($first_post_arr['added'], '');
        } else {
            $thread_starter = ($first_post_arr['username'] !== '' ? print_user_stuff($first_post_arr) : 'Lost [' . $topic_arr['user_id'] . ']') . '<br />' . get_date($first_post_arr['added'], '');
        }
        //==
        $icon = ($first_post_arr['icon'] == '' ? '<img src="' . $INSTALLER09['pic_base_url'] . 'forums/topic_normal.gif" alt="Topic" title="Topic" />' : '<img src="' . $INSTALLER09['pic_base_url'] . 'smilies/' . htmlsafechars($first_post_arr['icon']) . '.gif" alt="' . htmlsafechars($first_post_arr['icon']) . '" />');
        $first_post_text = tool_tip('<img src="' . $INSTALLER09['pic_base_url'] . 'forums/mg.gif" height="14" alt="Preview" title="Preview" />', format_comment($first_post_arr['body'], true, false, false) , 'First Post Preview');
        //=== last post read in topic
        $last_unread_post_res = sql_query('SELECT last_post_read FROM read_posts WHERE user_id=' . sqlesc($CURUSER['id']) . ' AND topic_id=' . sqlesc($topic_id));
        $last_unread_post_arr = mysqli_fetch_row($last_unread_post_res);
        $last_unread_post_id = ($last_unread_post_arr[0] > 0 ? $last_unread_post_arr[0] : $first_post_arr['first_post_id']);
        $did_i_post_here = sql_query('SELECT user_id FROM posts WHERE user_id=' . sqlesc($CURUSER['id']) . ' AND topic_id=' . sqlesc($topic_id));
        $posted = (mysqli_num_rows($did_i_post_here) > 0 ? 1 : 0);
        //=== add subscribed forum image
        $sub = sql_query('SELECT user_id FROM subscriptions WHERE user_id=' . sqlesc($CURUSER['id']) . ' AND topic_id=' . sqlesc($topic_id));
        $subscriptions = (mysqli_num_rows($sub) > 0 ? 1 : 0);
        //=== make the multi pages thing...
        $total_pages = floor($posts / $perpage);
        switch (true) {
        case ($total_pages == 0):
            $multi_pages = '';
            break;

        case ($total_pages > 11):
            $multi_pages = ' <span style="font-size: xx-small;"> <img src="' . $INSTALLER09['pic_base_url'] . 'forums/multipage.gif" alt="+" title="+" />';
            for ($i = 1; $i < 5; ++$i) {
                $multi_pages.= ' <a class="altlink" href="' . $INSTALLER09['baseurl'] . '/forums.php?action=view_topic&amp;topic_id=' . $topic_id . '&amp;page=' . $i . '">' . $i . '</a>';
            }
            $multi_pages.= ' ... ';
            for ($i = ($total_pages - 2); $i <= $total_pages; ++$i) {
                $multi_pages.= ' <a class="altlink" href="' . $INSTALLER09['baseurl'] . '/forums.php?action=view_topic&amp;topic_id=' . $topic_id . '&amp;page=' . $i . '">' . $i . '</a>';
            }
            $multi_pages.= '</span>';
            break;

        case ($total_pages < 11):
            $multi_pages = ' <span style="font-size: xx-small;"> <img src="' . $INSTALLER09['pic_base_url'] . 'forums/multipage.gif" alt="+" title="+" />';
            for ($i = 1; $i < $total_pages; ++$i) {
                $multi_pages.= ' <a class="altlink" href="' . $INSTALLER09['baseurl'] . '/forums.php?action=view_topic&amp;topic_id=' . $topic_id . '&amp;page=' . $i . '">' . $i . '</a>';
            }
            $multi_pages.= '</span>';
            break;
        }
        $new = ($arr_post_stuff['added'] > (TIME_NOW - $readpost_expiry)) ? (!$last_unread_post_res || $last_post_id > $last_unread_post_id) : 0;
        $topic_pic = ($posts < 30 ? ($locked ? ($new ? 'lockednew' : 'locked') : ($new ? 'topicnew' : 'topic')) : ($locked ? ($new ? 'lockednew' : 'locked') : ($new ? 'hot_topic_new' : 'hot_topic')));
        $topic_name = ($sticky ? '<img src="' . $INSTALLER09['pic_base_url'] . 'forums/pinned.gif" alt="Pinned" title="Pinned" /> ' : ' ') . ($topic_poll ? '<img src="' . $INSTALLER09['pic_base_url'] . 'forums/poll.gif" alt="Poll:" title="Poll" /> ' : ' ') . ' <a class="altlink" href="?action=view_topic&amp;topic_id=' . $topic_id . '">' . htmlsafechars($topic_arr['topic_name'], ENT_QUOTES) . '</a> ' . ($posted ? '<img src="' . $INSTALLER09['pic_base_url'] . 'forums/posted.gif" alt="Posted" title="Posted" /> ' : ' ') . ($subscriptions ? '<img src="' . $INSTALLER09['pic_base_url'] . 'forums/subscriptions.gif" alt="subscribed" title="Subcribed" /> ' : ' ') . ($new ? ' <img src="' . $INSTALLER09['pic_base_url'] . 'forums/new.gif" alt="New post in topic!" title="New post in topic!" />' : '') . $multi_pages;
        //=== change colors
        $colour = (++$colour) % 2;
        $class = ($colour == 0 ? 'one' : 'two');
        $rpic = ($topic_arr['num_ratings'] != 0 ? ratingpic_forums(ROUND($topic_arr['rating_sum'] / $topic_arr['num_ratings'], 1)) : '');
        //=== delete thread  //= .$delete_me
        if ($CURUSER['class'] == UC_MAX && $forum_id === 2) //=== set this to your forum that you don't want to bother with the sanity check
        {
            $delete_me = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="font-size: x-small;">[ <a class="altlink" href="' . $INSTALLER09['baseurl'] . '/forums.php?action=delete_topic&amp;topic_id=' . $topic_id . '&amp;sure=1&amp;send_me_back=666">Delete</a> ]</span>';
        }
        $content.= '<tr>
		<td class="' . $class . '" align="center"><img src="' . $INSTALLER09['pic_base_url'] . 'forums/' . $topic_pic . '.gif" alt="topic" title="Topic" /></td>
		<td class="' . $class . '" align="center">' . $icon . '</td>
		<td align="left" valign="middle" class="' . $class . '">
		<table border="0" cellspacing="0" cellpadding="0">
		<tr>
		<td  class="' . $class . '" align="left">' . $topic_name . $first_post_text . $topic_status_image . '</td>
		<td class="' . $class . '" align="right">' . $rpic . '</td>
		</tr>
		</table>
		' . ($topic_arr['topic_desc'] !== '' ? '&#9658; <span style="font-size: x-small;">' . htmlsafechars($topic_arr['topic_desc'], ENT_QUOTES) . '</span>' : '') . '</td>
		<td align="center" class="' . $class . '">' . $thread_starter . '</td>
		<td align="center" class="' . $class . '">' . number_format($topic_arr['post_count']) . '</td>
		<td align="center" class="' . $class . '">' . number_format($topic_arr['views']) . '</td>
		<td align="center" class="' . $class . '"><span style="white-space:nowrap;">' . get_date($arr_post_stuff['added'], '') . '</span><br />
		<a class="altlink" href="' . $INSTALLER09['baseurl'] . '/forums.php?action=view_topic&amp;topic_id=' . $topic_id . '&amp;page=' . $last_post_id . '#' . $last_post_id . '" title="Go to the last post in this thread">Last Post</a> by&nbsp;' . $last_post_username . '</td>
		<td align="center" class="' . $class . '">' . $post_status_image . ' <a class="altlink" href="' . $INSTALLER09['baseurl'] . '/forums.php?action=view_topic&amp;topic_id=' . $topic_id . '&amp;page=' . $last_unread_post_id . '#' . $last_unread_post_id . '" title="last unread post in this thread">
		<img src="' . $INSTALLER09['pic_base_url'] . 'forums/last_post.gif" alt="last post" title="Last Post" /></a></td>
		</tr>';
    }
    $the_top_and_bottom = '<table border="0" cellspacing="0" cellpadding="5" width="100%">
		<tr><td class="three" width="33%" align="left"></td>
		<td class="three" width="33%" align="center">' . (($count > $perpage) ? $menu : '') . '</td>
		<td class="three"  width="34%" align="right">' . ($locked == 'yes' ? '<span style="font-weight: bold; font-size: x-small;">
		This topic is locked... No new posts are allowed.</span>' : ($may_post ? '<form action="' . $INSTALLER09['baseurl'] . '/forums.php" method="post" name="new">
		<input type="hidden" name="action" value="new_topic" />
		<input type="hidden" name="forum_id" value="' . $forum_id . '" />
		<input type="submit" name="button" class="button" value="New Topic" onmouseover="this.className=\'button_hover\'" onmouseout="this.className=\'button\'" />
		</form>' : '<span style="font-weight: bold; font-size: x-small;">
		You are not permitted to post in this forum.</span>')) . '</td></tr></table>';
} else {
    $content.= '<tr><td align="center" class="clear" colspan="8">
	<span style="font-weight: bold; text-align: center;">No topics found</span><br />
		' . ($may_post ? '<form action="' . $INSTALLER09['baseurl'] . '/forums.php" method="post" name="new">
		<input type="hidden" name="action" value="new_topic" />
		<input type="hidden" name="forum_id" value="' . $forum_id . '" />
      <input type="submit" name="button" class="button" value="Start New Topic" onmouseover="this.className=\'button_hover\'" onmouseout="this.className=\'button\'" />
		</form></td></tr>' : '<span style="font-weight: bold; font-size: x-small;">You are not permitted to post in this forum.</span>');
    $the_top_and_bottom = '';
}
$HTMLOUT.= $location_bar . '<br />' . $sub_forums . '
		<table border="0" cellspacing="0" cellpadding="5" width="90%">
		<tr><td align="center" class="clear" colspan="8">
		' . $the_top_and_bottom . '
		</td>
		</tr>
		' . ($count == 0 ? '' : '<tr>
		<td align="center" valign="middle" class="forum_head_dark" width="10"><img src="' . $INSTALLER09['pic_base_url'] . 'forums/topic.gif" alt="topic" title="Topic" /></td>
		<td align="center" valign="middle" class="forum_head_dark" width="10"><img src="' . $INSTALLER09['pic_base_url'] . 'forums/topic_normal.gif" alt="Thread Icon" title="Thread Icon" /></td>
		<td align="left" class="forum_head_dark">Topic</td>
		<td align="center" class="forum_head_dark">Started By</td>
		<td class="forum_head_dark" align="center" width="10">Replies</td>
		<td class="forum_head_dark" align="center" width="10">Views</td>
		<td align="center" class="forum_head_dark" width="140">Last Post</td>
		<td align="center" valign="middle" class="forum_head_dark" width="10"><img src="' . $INSTALLER09['pic_base_url'] . 'forums/last_post.gif" alt="last post" title="Last Post" /></td>
		</tr>') . $content . '
		<tr><td align="center" class="clear" colspan="8">
		' . $the_top_and_bottom . '</td>
		</tr></table><br />' . $location_bar;
?>
