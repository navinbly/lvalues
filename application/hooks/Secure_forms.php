<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Secure_forms
{
    public function inject()
    {
        $CI =& get_instance();
        $output = $CI->output->get_output();
        if ($output === '' || stripos($output, '<html') === false) return;

        $name = $CI->security->get_csrf_token_name();
        $hash = $CI->security->get_csrf_hash();
        $hidden = '<input type="hidden" name="' . html_escape($name) . '" value="' . html_escape($hash) . '">';

        $output = preg_replace_callback('/<form\b([^>]*)>/i', function($matches) use ($name, $hidden) {
            $attributes = $matches[1];
            if (!preg_match('/\bmethod\s*=\s*([\'"]?)post\1/i', $attributes)) return $matches[0];
            if (stripos($attributes, $name) !== false) return $matches[0];
            return $matches[0] . $hidden;
        }, $output);

        $meta = '<meta name="csrf-param" content="' . html_escape($name) . '">'
            . '<meta name="csrf-token" content="' . html_escape($hash) . '">';
        if (stripos($output, '</head>') !== false) {
            $output = preg_replace('/<\/head>/i', $meta . '</head>', $output, 1);
        }

        $script = '<script>(function(){'
            . 'var n=' . json_encode($name) . ',t=' . json_encode($hash) . ';'
            . 'document.addEventListener("submit",function(e){var f=e.target;if(!f||String(f.method).toLowerCase()!=="post"||f.elements[n])return;var i=document.createElement("input");i.type="hidden";i.name=n;i.value=t;f.appendChild(i);},true);'
            . 'var of=window.fetch;if(of){window.fetch=function(u,o){o=o||{};var m=String(o.method||"GET").toUpperCase();if(m==="POST"&&o.body instanceof FormData&&!o.body.has(n))o.body.append(n,t);if(m==="POST"&&o.body instanceof URLSearchParams&&!o.body.has(n))o.body.append(n,t);return of.call(this,u,o);};}'
            . 'if(window.jQuery){jQuery.ajaxPrefilter(function(o){if(String(o.type||"GET").toUpperCase()!=="POST")return;if(o.data instanceof FormData){if(!o.data.has(n))o.data.append(n,t);}else if(typeof o.data==="string"){if(o.data.indexOf(encodeURIComponent(n)+"=")<0)o.data+=(o.data?"&":"")+encodeURIComponent(n)+"="+encodeURIComponent(t);}else{o.data=o.data||{};o.data[n]=t;}});}'
            . '})();</script>';
        if (stripos($output, '</body>') !== false) {
            $output = preg_replace('/<\/body>/i', $script . '</body>', $output, 1);
        }
        $CI->output->set_output($output);
    }
}
