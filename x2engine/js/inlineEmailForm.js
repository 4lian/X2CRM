/*********************************************************************************
 * The X2CRM by X2Engine Inc. is free software. It is released under the terms of 
 * the following BSD License.
 * http://www.opensource.org/licenses/BSD-3-Clause
 * 
 * X2Engine Inc.
 * P.O. Box 66752
 * Scotts Valley, California 95066 USA
 * 
 * Company website: http://www.x2engine.com 
 * Community and support website: http://www.x2community.com 
 * 
 * Copyright © 2011-2012 by X2Engine Inc. www.X2Engine.com
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without modification, 
 * are permitted provided that the following conditions are met:
 * 
 * - Redistributions of source code must retain the above copyright notice, this 
 *   list of conditions and the following disclaimer.
 * - Redistributions in binary form must reproduce the above copyright notice, this 
 *   list of conditions and the following disclaimer in the documentation and/or 
 *   other materials provided with the distribution.
 * - Neither the name of X2Engine or X2CRM nor the names of its contributors may be 
 *   used to endorse or promote products derived from this software without 
 *   specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND 
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED 
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. 
 * IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, 
 * INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, 
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF 
 * LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE 
 * OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED 
 * OF THE POSSIBILITY OF SUCH DAMAGE.
 ********************************************************************************/


$(function() {
	initX2EmailForm();
});

/**
 *	initX2EmailForm
 *	
 *	Set up attachments in the email form so that the attachments div is droppable for
 *  files dragged over from the media widget. This is called when the page loads (if the
 *  page has an inline email form) and whenever the email form is replaced, like after an
 *  ajax call from pressing the preview button.
 *
 */
function initX2EmailForm() {
	$('#email-attachments').droppable({
		accept: '.media',
		activeClass: 'x2-state-active',
		hoverClass: 'x2-state-hover',
		drop: function(event, ui) {

			var media = ui.draggable.context;
			
			var mediaId = media.href.split('/').pop();
			var mediaName = media.innerHTML;

            var file = $('<input>', {
                'type': 'hidden',
                'name': 'AttachmentFiles[id][]',
                'class': 'AttachmentFiles',
                'value': mediaId, // name of temp file
            });		

            var temp = $('<input>', { 
                'type': 'hidden',
                'name': 'AttachmentFiles[temp][]',
                'value': false, // indicates that this is not a temp file
            });
            
           	var remove = $("<a>", {
           	    'href': "#",
           	    'html': "[x]",
           	});
            
			var attachment = $('.next-attachment');
			var newFileChooser = attachment.clone();
			attachment.children('.error').html(''); // clear attachment errors (if any)
			
			attachment.removeClass('next-attachment');
			
			attachment.append(temp);
			attachment.append(file);
			attachment.find('.filename').html(mediaName);
			attachment.find('.remove').append(remove);
			attachment.find('.upload-wrapper').remove();
			
            remove.click(function() {attachment.remove(); return false;});
			
			attachment.after(newFileChooser);
			initX2FileInput();
		}
	});
	
	initX2FileInput();
	
	$(document).mouseup(function() {
		$('input.x2-file-input[type=file]').next().removeClass('active');
	});
	
	// init remove button for attachment that were carried over after pressing "Preview"
	$('#email-attachments').find('.remove a').each(function() {
		$(this).click(function() {$(this).parent().parent().remove(); return false;});
	});
	
	// init cc and bcc buttons
	$('#cc-toggle').click(function() {
	    $(this).animate({
	    		opacity: 'toggle',
	    		width: 0
	    	}, 400);
	    
	    $('#cc-row').slideDown(300);
	});
	
	$('#bcc-toggle').click(function() {
	    $(this).animate({
	    		opacity: 'toggle',
	    		width: 0
	    	}, 400);
	    
	    $('#bcc-row').slideDown(300);
	});
}