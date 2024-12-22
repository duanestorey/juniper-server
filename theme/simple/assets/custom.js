
const observer = new IntersectionObserver((entries, observer) => {
	entries.forEach((entry) => {
		if (entry.isIntersecting) {
			console.log("Initializing Disqus!");

			// Starting Disqus's universal embed code.
			(function() {
				var d = document, s = d.createElement('script');
				s.src = 'https://duanestorey.disqus.com/embed.js';
				s.setAttribute('data-timestamp', +new Date());
				(d.head || d.body).appendChild(s);
			})();
			// Ending Disqus's universal embed code.

			// Stop observing to prevent reinitializing Disqus.
			observer.unobserve(entry.target);
		}
	});
});

// Start listening:
const mountNode = document.querySelector( "#disqus_thread" );
if ( mountNode != null ) {
    observer.observe( mountNode );
}
