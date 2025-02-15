(function () {
  farmOS.map.behaviors.rothamsted_snapshot = {
    attach: function (instance) {
      instance.addBehavior("snapshot");
    },
  };
}(drupalSettings));
