(function () {
  farmOS.map.behaviors.farm_rothamsted_experiment_plot_layer = {
    attach: function (instance) {
      const planId = instance.farmMapSettings.farm_rothamsted_experiment_plot_layer.plan;
      var url = new URL(
        `/plan/${planId}/plots/geojson`,
        window.location.origin + drupalSettings.path.baseUrl
      )
      var newLayer = instance.addLayer('geojson', {
        title: Drupal.t('Plots'),
        url,
        color: 'orange'
      })
      var source = newLayer.getSource()
      source.on('change', function () {
        instance.zoomToVectors()
      })
    }
  }
}())
