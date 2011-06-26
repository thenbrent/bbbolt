/**
 * jQuery password strength plugin
 *
 * jquery.strengthy.js
 *
 * Minified with http://www.refresh-sf.com/yui/
 *
 * @author    Lupo Montero <lupo@e-noise.com>
 * @copyright 2010 E-NOISE.COM LIMITED
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 * @license   http://www.gnu.org/licenses/gpl-3.0.txt GPL v3 License
 */

(function(c){var d={minLength:8,showToggle:true,errorClass:"strengthy-error",validClass:"strengthy-valid",showMsgs:true,require:{numbers:true,upperAndLower:true,symbols:true},msgs:["Password is too short.","Password must contain at least one number.","Password must contain both lower case and upper case characters.","Password must contain at least one symbol (ie: %!£@).","Password is valid.","Show password"]};var b=function(f,g,e){return function(i,h){g.attr("title",i);if(f.showMsgs){e.attr("class",h).html(i)}}};var a=function(f){var e=[{name:"numbers",regex:/\d/,msg:f.msgs[1]},{name:"upperAndLower",regex:/([a-z].*[A-Z]|[A-Z].*[a-z])/,msg:f.msgs[2]},{name:"symbols",regex:/[^a-zA-Z0-9]/,msg:f.msgs[3]}];return function(l,h){var j=l.val();var m=0;var k=0;var g;l.removeClass(f.validClass);if(j.length<+f.minLength){h(f.msgs[0],f.errorClass);return false}for(g=0;g<e.length;g++){if(f.require[e[g].name]!==true){continue}k++;if(e[g].regex.test(j)){m+=1}else{h(e[g].msg,f.errorClass)}}if(m/k===1){h(f.msgs[4],f.validClass);l.addClass(f.validClass)}}};c.fn.strengthy=function(e){var f=c.extend(d,e);var g=a(f);return this.each(function(){var k=c(this);var l=k.attr("name");var i;var j;var h;k.after('<span id="strengthy-msg-'+l+'"></span>');i=c("#strengthy-msg-"+l);j=b(f,k,i);if(f.showToggle){k.before('<input type="text" id="strengthy-show-toggle-plain-'+l+'" style="display: none;" />');i.after('<p class="strengthy-show-toggle"><input id="strengthy-show-toggle-'+l+'" type="checkbox" tabindex="-1" />'+f.msgs[5]+"</p>");h=c("#strengthy-show-toggle-plain-"+l);h.keyup(function(){k.val(h.val()).keyup()});c("#strengthy-show-toggle-"+l).click(function(){if(k.css("display")==="none"){k.css("display","inline");h.css("display","none")}else{k.css("display","none");h.css("display","inline")}})}k.keyup(function(){if(h.length>0){h.val(k.val())}g(k,j)})})}})(jQuery);
