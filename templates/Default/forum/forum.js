(function () {
  function setBlockCollapsed(block, collapsed) {
    if (!block) {
      return;
    }
    block.classList.toggle("mybb-collapsed", collapsed);
    var icon = block.querySelector(".mybb-cat-header .mybb-cat-toggle i");
    if (icon) {
      icon.className = collapsed ? "fa fa-plus" : "fa fa-minus";
    }
  }

  function toggleBlock(block) {
    setBlockCollapsed(block, !block.classList.contains("mybb-collapsed"));
  }

  document.addEventListener("click", function (e) {
    var header = e.target.closest(".mybb-cat-header");
    if (!header || !header.querySelector(".mybb-cat-toggle")) {
      return;
    }
    var block = header.closest(".mybb-category-block, .mybb-stats-card");
    if (!block) {
      return;
    }
    e.preventDefault();
    toggleBlock(block);
  });
})();
