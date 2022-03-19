(function () {
  farmOS.map.behaviors.farm_rothamsted_experiment_plot_layer = {
    attach: function (instance) {
      const layerOptions = instance.farmMapSettings.farm_rothamsted_experiment_plot_layer;
      var url = new URL(
        `/plan/${layerOptions.plan}/plots/geojson`,
        window.location.origin + drupalSettings.path.baseUrl
      )

      // Include provided filters.
      const filters = layerOptions.filters ?? {};
      Object.entries(filters).forEach( ([key, value]) => {
        if (Array.isArray(value)) {
          for (let i = 0; i < value.length; i++) {
            url.searchParams.append(key + '[]', value[i]);
          }
        }
        else {
          url.searchParams.append(key, value);
        }
      });

      // Create the layer.
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
