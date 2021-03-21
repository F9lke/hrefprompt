/**
 * @name: prompter
 * @package: js
 * @description: Script to implement the prompt-on-external-href-click functionality
 * @author: Florian Götzrath <info@floriangoetzrath.de>
 */

/** @var {String} msg constant to store the prompt messages */
const { msg } = prompter;
/** @var {String} confstyle constant to store the configure prompt type */
const { confstyle } = prompter;

/** @var {Array} external_links process variable to store all external links detected */
let external_links = [];
/** @var {Array} old_external_link process variable to store a copy of all links flagged as "external" in the last function call */
let old_external_links = [];

/** @var {HTMLElement|null} modalEl process variable to store the modal representation in the dom */
let modalEl = null;

/**
 * Filters external links, distributes the event for a confirmation window showing the configured prompt message
 */
const distributeLinkPrompter = () => {
	
	// Filter for all external links on the page
	Array.from(document.links).forEach(a => {
		if(
			location.hostname !== a.hostname
			&& a.hostname.length
			&& !a.href.match(/^mailto\:/)
			&& external_links.indexOf(a) === -1
		) {
			external_links.push(a);
		}
	});
	
	// If no external links could be identified, cancel
	if(external_links.length === 0) return false;
	
	// If this is not the first execution and there are no new entries to external_links, cancel
	if(old_external_links.length > 0 && external_links.length === old_external_links.length) return false;
	
	// Apply the confirmation window event to all external links
	external_links.forEach(a => {
		a.onclick = e => {
			if(confstyle === "confirmation") {
				
				// Show the confirmation box
				let confirmationRes = window.confirm(msg.content);
				
				// If the user declines the redirect, cancel the action
				if(!confirmationRes) e.preventDefault();
				
			} else if(confstyle === "modal") {
				
				// Cancel the redirect
				e.preventDefault();
				
				// Display the modal
				modalEl.classList.add('modal-opened');
				
				// Refresh the redirect-event with the new hyper-reference
				modalEl.querySelector(".modal-redirect").onclick = () => { 
					window.location.href = e.target.getAttribute("href") != null 
						? e.target.getAttribute("href") 
						: e.target.closest("a").getAttribute("href");
				}
			}
		}
	});
	
	old_external_links = external_links;
	
}; // inline function distributeLinkPrompter()

/**
 * Writes the modal template to the dom
 */
const injectModalTemplate = () => {
	
	// Create the element
	modalEl = document.createElement("div");
	
	modalEl.id = "conf-modal-window";
	modalEl.classList.add('modal-window');
	modalEl.innerHTML =     '  <div>\n' +
						    '    <a title="' + msg.close + '" class="modal-close modal-close-top">' + msg.close + '</a>\n' +
							'    <h1>' + msg.title + '</h1>\n' +
							'    <div class="msg">' + msg.content + '</div>\n' +
							'    <div class="modal-bot"><small>' + msg.actions + '</small></div>\n' +
							'    <a class="btn button modal-close modal-close-bot modal-bot">' + msg.cancel + '</a>\n' +
							'    <a class="btn button modal-redirect modal-redirect-bot modal-bot">' + msg.redirect + '</a>\n' +
							'  </div>';

	// Strip reference mutating characters left
	modalEl.querySelectorAll("a[href]").forEach(function(a) {
		modalEl.innerHTML = modalEl.innerHTML.replace(
			a.outerHTML, 
			a.outerHTML.replace("\\&quot;", "").replace("\\&quot;", "")
		);
	});
	
	// Inject it to the dom
	document.querySelector("body").insertAdjacentElement('beforeend', modalEl);
	
	// Hide the bottom part in case custom templating of the action buttons as been configured
	if(msg.content.indexOf("modal-close-text") !== -1 && msg.content.indexOf("modal-redirect-text") !== -1) {
		document.querySelectorAll(".modal-bot").forEach(el => {
			el.classList.add("bot-hidden");
		})
	}
	
	// Cast the exit-modal-event
	let modalCloseTriggers = [modalEl];
	
	modalEl.querySelectorAll("a.modal-close").forEach(el => modalCloseTriggers.push(el));
	modalCloseTriggers.forEach(el => el.onclick = () => modalEl.classList.remove('modal-opened'));
	
}; // inline function injectModalTemplate()

/**
 * Define hooks for the prompter
 */

// On page load
window.onload = () => {
	
	distributeLinkPrompter();
	
	if(confstyle === "modal") injectModalTemplate();
	
};

// On anchor add due to lazy-loading or ajax based dom manipulations
document.addEventListener("DOMNodeInserted", e => {
	if(!!e.target.tagName)
		if(e.target.tagName.toLowerCase() === "a") distributeLinkPrompter();
});