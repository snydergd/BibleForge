/**
 * BibleForge
 *
 * @date    09-28-10
 * @version 0.2 alpha
 * @link    http://BibleForge.com
 * @license Reciprocal Public License 1.5 (RPL1.5)
 * @author  BibleForge <info@bibleforge.com>
 */

/// Set JSLint options.
/*global window, BF */
/*jslint white: true, browser: true, devel: true, evil: true, forin: true, onevar: true, undef: true, nomen: true, bitwise: true, newcap: true, immed: true */

/**
 * Load secondary, nonessential code, such as the wrench button.
 *
 * @param  context (object) An object containing necessary variables from the parent closure.
 * @note   This code is eval'ed inside of main.js.  The anonymous function is then called and data from the BibleForge closure is passed though the context object.
 * @note   Even though this function is not called immediately, it needs to be wrapped in parentheses to make it a function expression.
 * @return Null.
 */
(function (context)
{
    /**
     * Create the show_context_menu() function with closure.
     *
     * @note   This function is called immediately.
     * @return The function for that is used to create the menu.
     */
    var show_context_menu = (function ()
    {
        var context_menu = document.createElement("div"),
            is_open      = false;
        
        ///NOTE: The default style does has "display" set to "none" and "position" set to "fixed."
        context_menu.className = "contextMenu";
        
        document.body.insertBefore(context_menu, null);
        
        /**
         * Close the context menu.
         *
         * @example close_menu(open_menu); /// Closes the menu and then runs the open_menu() function.
         * @example close_menu();          /// Closes the menu and does nothing else.
         * @param   callback (function) (optional) The function to run after the menu is closed.
         * @note    Called by show_context_menu() and document.onclick().
         * @return  NULL.
         */
        function close_menu(callback)
        {   
            /// First, stop the element from being displayed.
            context_menu.style.display = "none";
            /// Then reset the opacity so that it will fade in when the menu is re-displayed later.
            context_menu.style.opacity = 0;
            
            /// A delay is needed so that if there is a callback, it will run after the menu has been visually removed from the page.
            window.setTimeout(function ()
            {
                /// Set the menu's is_open status to false after the delay to prevent the menu from being re-opened in the meantime.
                is_open = false;
                
                if (callback) {
                    callback();
                }
            }, 0);
        }
        
        
        /**
         * Display the context menu.
         *
         * @example open_menu(leftOffset, topOffset, [{text: "Menu Item 1", link: "http://link.com"}, [text: "Menu Item 2", link: some_function, true}]); /// Creates a menu with one external link and one link that runs a function with a line break separating the two.
         * @param   x_pos      (number) The X position of the menu.
         * @param   y_pos      (number) The Y position of the menu.
         * @param   menu_items (array)  An array containing object(s) specifying the text of the menu items, the corresponding links, and whether or not to add a line break.
         *                              Array format: [{text: (string), link: (string or function), line: (truthy or falsey (optional))}, {...}]
         * @note    Called by show_context_menu() and close_menu() (as the callback function).
         * @return  NULL.
         */
        function open_menu(x_pos, y_pos, menu_items)
        {
            var i,
                menu_container        = document.createElement("div"),
                menu_count            = menu_items.length,
                menu_item,
                prev_document_onclick = document.onclick ? document.onclick : function () {};
            
            is_open = true;
            
            for (i = 0; i < menu_count; ++i) {
                menu_item = document.createElement("a");
                
                /// If the link is a string, then it is simply a URL; otherwise, it is a function.
                if (typeof menu_items[i].link == "string") {
                    menu_item.href = menu_items[i].link;
                } else {
                    ///TODO: Create a useful hash value.
                    menu_item.href    = "#";
                    menu_item.onclick = menu_items[i].link;
                }
                /// Should there be a line break before this item?
                if (menu_items[i].line) {
                    menu_item.style.borderTop = "1px solid #A3A3A3";
                }
                
                /// document.createTextNode() is akin to innerText.  It does not inject HTML.
                menu_item.appendChild(document.createTextNode(menu_items[i].text));
                menu_container.appendChild(menu_item);
            }
            
            /// The menu needs to be cleared first.
            ///TODO: Determine if there is a better way to do this.  Since the items are contained in a single <div> tag, it should not be slow.
            context_menu.innerHTML = "";
            
            context_menu.appendChild(menu_container);
            
            ///TODO: Determine if the menu will go off of the page.
            context_menu.style.cssText = "left:" + x_pos + "px;top:" + y_pos + "px;display:inline";
            
            ///TODO: Determine if it would be good to also close the menu on document blur.
            /**
             * Catch mouse clicks in order to close the menu.
             *
             * @note   Called on the mouse click event anywhere on the page (unless the event is canceled).
             * @return NULL.
             * @bug    Firefox 3.6 Does not close the menu when clicking the query box the first time.  However, it does close after submitting the query.
             */
            document.onclick = function ()
            {
                /// Close the context menu if the user clicks the page.
                close_menu();
                
                /// Re-assign the onclick() code back to document.onclick now that this code has finished its purpose.
                ///TODO: If multiple functions attempt to reassign a global event function, there could be problems; figure out a better way to do this,
                ///      such as creating a function that handles all event re-assignments and attaching it to the BF object.
                document.onclick = prev_document_onclick;
                /// Run any code that normally would have run when the page is clicked.
                prev_document_onclick();
            };
            
            /// A delay is needed in order for the CSS transition to occur.
            window.setTimeout(function ()
            {
                context_menu.style.opacity = 1;
            }, 0);
        }
        
        
        /**
         * Handle opening the context menu, even if one is already open.
         *
         * @example show_context_menu(leftOffset, topOffset, [{text: "Menu Item 1", link: "http://link.com"}, [text: "Menu Item 2", link: some_function, true}]); /// Creates a menu with one external link and one link that runs a function with a line break separating the two.
         * @param   x_pos      (number) The X position of the menu.
         * @param   y_pos      (number) The Y position of the menu.
         * @param   menu_items (array)  An array containing object(s) specifying the text of the menu items, the corresponding links, and whether or not to add a line break.
         *                              Array format: [{text: (string), link: (string or function), line: (truthy or falsey (optional))}, {...}]
         * @note    This is the function stored in the show_context_menu variable.
         * @note    Called by the wrench menu onclick event.
         * @return  NULL.
         */
        return function (x_pos, y_pos, menu_items)
        {
            /// If it is already open, close it and then re-open it with the new menu.
            if (is_open) {
                close_menu(function ()
                {
                    open_menu(x_pos, y_pos, menu_items);
                });
            } else {
                open_menu(x_pos, y_pos, menu_items);
            }
        };
    }());
    
    
    /**
     * Display the panel window.
     *
     * @return NULL.
     */
    function show_panel()
    {
        
    }
    
    
    /**
     * Add the rest of the BibleForge user interface (currently, just the wrench menu).
     *
     * @note   This function is called immediately.
     * @return NULL.
     */
    (function ()
    {
        var wrench_button = document.createElement("input"),
            wrench_label  = document.createElement("label");
        
        ///NOTE: An IE 8 bug(?) prevents modification of the type attribute after an element is attached to the DOM, so it must be done earlier.
        wrench_button.type  = "image";
        wrench_button.id    = "wrenchIcon" + context.viewPort_num;
        ///TODO: Determine where this gif data should be.
        wrench_button.src   = "data:image/gif;base64,R0lGODdhEAAQAMIIAAEDADAyL05OSWlpYYyLg7GwqNjVyP/97iwAAAAAEAAQAAADQ3i6OwBhsGnCe2Qy+4LRS3EBn5JNxCgchgBo6ThwFDc+61LdY6m4vEeBAbwMBBHfoYgBLW8njUPmPNwk1SkAW31yqwkAOw==";
        wrench_button.title = BF.lang.wrench_title;
        
        wrench_label.htmlFor = wrench_button.id;
        
        /// A label is used to allow the cursor to be all the way in the corner and still be able to click on the button.
        ///NOTE: Because of a bug in WebKit, the elements have to be attached to the DOM before setting the className value.
        ///TODO: Report the WebKit (or Chrome?) bug.
        wrench_label.appendChild(wrench_button);
        context.topBar.insertBefore(wrench_label, context.topBar.childNodes[0]);
        
        /// Make the elements transparent at first and fade in (using a CSS transition).
        wrench_label.className = "wrenchPadding transparent";
        ///NOTE: In order for the CSS transition to occur, there needs to be a slight delay.
        window.setTimeout(function ()
        {
            wrench_label.className = "wrenchPadding";
        }, 0);
        
        wrench_button.className = "wrenchIcon";
        
        /**
         * Prepare to display the context menu near the wrench button.
         *
         * @param  e (object) The event object optionally sent by the browser.
         * @note   Called when the user clicks on the wrench button.
         * @return NULL.
         * @todo   Make the wrench icon look pressed.
         * @bug    Opera does not send the onclick event from the label to the button.
         */
        wrench_button.onclick = function (e)
        {
            var wrench_pos = BF.get_position(wrench_button);
            
            ///TODO: These need to be language specific.
            show_context_menu(wrench_pos.left, wrench_pos.top + wrench_button.offsetHeight, [{text: "Configure", link: show_panel}, {line: 1, text: "Blog", link: "http://blog.bibleforge.com"}, {text: "Help", link: show_panel}]);
            
            /// Stop the even from bubbling so that document.onclick() does not fire and attempt to close the menu immediately.
            ///TODO: Determine if stopping propagation causes or could cause problems with other events.
            ///NOTE: IE 8- does not pass the event variable to the function, so the global event variable must be retrieved.
            /*@cc_on
                @if (@_jscript_version < 9)
                    e = window.event;
                    e.cancelBubble = true;
                @end
            @*/
            /// Mozilla/WebKit/Opera/IE 9
            if (e.stopPropagation) {
                e.stopPropagation();
            }

        };
    }());
});
