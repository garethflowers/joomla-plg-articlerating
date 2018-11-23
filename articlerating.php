<?php

/**
 * Article Rating Plugin
 *
 * @version $Id: rating.php 1.0 November 2009$
 * @package ArticleRating
 * @subpackage Plugins
 * @Copyright (C) 2009 Gareth Flowers. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @author Gareth Flowers
 * @link http://garethflowers.com/joomlaarticlerating/
 */

defined( '_JEXEC' ) or exit( 'Restricted access' );
jimport( 'joomla.event.plugin' );

$mainframe->registerEvent( 'onPrepareContent', 'getArticleRating' );

global $css_added;

function getArticleRating( &$row, &$params, $page = 0 )
{
    // simple performance check to determine whether plugin should process further
    if ( JString::strpos( $row->text, 'rating' ) === false )
    {
        return true;
    }

    $matches = null;
    $plugin = null;

    // define the regular expression for the plugin
    $regex = "#{rating}([1-9]*)(.5)?{/rating}#s";
    preg_match_all( $regex, $row->text, $matches, PREG_SET_ORDER );

    // Get plugin info
    $plugin = & JPluginHelper::getPlugin( 'content', 'articlerating' );

    // get parameters
    $pluginParams = new JParameter( $plugin->params );

    // check whether plugin has been unpublished
    if ( !$pluginParams->get( 'enabled', 1 ) )
    {
        $row->text = preg_replace( $regex, '', $row->text );
        return true;
    }

    // perform the replacement
    if ( count( $matches ) )
    {
        $style = '<link href="' . JURI::base() . 'plugins/content/articlerating/css/style-rating.css" rel="stylesheet" type="text/css" />';
        JApplication::addCustomHeadTag( $style );

        // grab all parameters not already found
        $plg = array(
            'show' => $pluginParams->get( 'show', 0 ),
            'beforetext' => $pluginParams->get( 'beforetext', '' ),
            'aftertext' => $pluginParams->get( 'aftertext', '' ),
            'imageset' => $pluginParams->get( 'imageset', 'star' ),
            'imagecustom' => $pluginParams->get( 'imagecustom', '' ),
            'imagecustomhalf' => $pluginParams->get( 'imagecustomhalf', '' ),
            'outof' => $pluginParams->get( 'outof', 5 )
        );

        // find number of matches
        $count = count( $matches[0] );

        // loop through each match
        for ( $i = 0; $i <= $count; $i++ )
        {
            // get the rating
            $rating = $matches[$i][1] . $matches[$i][2];

            // build html for images representation
            $output_image = '';
            if ( $plg['show'] == 0 || $plg['show'] == 1 )
            {
                $output_image = '<span id="articlerating-img">';
                for ( $j = 1; $j <= $rating; $j++ )
                {
                    if ( $plg['imageset'] == 'custom' )
                    {
                        $output_image .= '<img src="' . $plg['imagecustom'] . '" />';
                    }
                    else
                    {
                        $output_image .= '<img src="' . JURI::base() . 'plugins/content/articlerating/images/' . $plg['imageset'] . '.png" />';
                    }
                }

                if ( $matches[$i][2] == '.5' )
                {
                    if ( $plg['imageset'] == 'custom' )
                    {
                        $output_image .= '<img src="' . $plg['imagecustomhalf'] . '" />';
                    }
                    else
                    {
                        $output_image .= '<img src="' . JURI::base() . 'plugins/content/articlerating/images/' . $plg['imageset'] . '-half.png" />';
                    }
                }

                $output_image .= '</span>';
            }

            // build html for text representation
            $output_text = $plg['show'] == 0 || $plg['show'] == 2 ? '<span id="articlerating-text">' . $rating . ' / ' . $plg['outof'] . '</span>' : '';

            // combine the final html pieces
            $output = '<span id="articlerating" title="Rated : ' . $rating . ' out of ' . $plg['outof'] . '" />';

            if ( !empty( $plg['beforetext'] ) )
            {
                $output .= '<span id="articlerating-before">' . $plg['beforetext'] . '</span>';
            }

            $output .= $pluginParams->get( 'imagetextorder', 1 ) == 1 ? $output_image . $output_text : $output_text . $output_image;

            if ( !empty( $plg['aftertext'] ) )
            {
                $output .= '<span id="articlerating-after">' . $plg['aftertext'] . '</span>';
            }

            $output .= '</span>';

            // replace the rating keywords with the generated html
            $row->text = str_replace( $matches[$i][0], $output, $row->text );
        }

        // replace any remaining ratings with nothing
        $row->text = preg_replace( $regex, '', $row->text );
    }

    return true;
}
