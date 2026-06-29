// Only initialize Quill if the editor container exists and we're not on preview page
if (!window.PreviewPageActive && document.querySelector("#editor")) {
  var quill = new Quill("#editor", {
    theme: "snow",
  });
} else if (!document.querySelector("#editor")) {
  console.warn('Quill editor container not found - skipping initialization');
}
