<!---
Full module name and description.
-->
# farm_rothamsted
Custom farmOS features for [Rothamsted Research](https://www.rothamsted.ac.uk/)

This module is a custom add-on for the [farmOS](http://drupal.org/project/farm)
distribution. This module has been designed for Rothamsted; there is no
support for module being used in other production use cases.

<!---
Document features the module provides.
-->
## Features

### Experiments

The `farm_rothamsted_experiment` submodule provides an experiment plan type
used for managing a broad range of experiments at Rothamsted.

- Provides an additional `plot` asset type.
- Form for importing experiments and plots from CSV + geojson files.
- Various fields to store metadata associated with an experiment.
- Flexible treatment factor and treatment factor level system.
- Custom table views of Plots associated with an experiment.

### Quick forms

The `farm_rothamsted_quick` submodule provides custom quick forms:
- Commercial asset
- Drilling
- Fertiliser
- Operation
- Spraying
- Harvest (combine)
- Harvest (trailer)

Actions are provided to help complete quick forms for specific plots associated
with an experiment.

<!---
It might be nice to include a FAQ.
-->
## FAQ

<!---
Include maintainers.
-->
## Maintainers

Current maintainers:
- Paul Weidner [@paul121](https://github.com/paul121)
- Mike Stenta [@mstenta](https://github.com/mstenta)
- Aislinn Pearson [@aislinnpearson](https://github.com/aislinnpearson)

<!---
Include sponsors.
-->
## Sponsors
This project has been sponsored by:
- [Rothamsted Research](https://www.rothamsted.ac.uk/)
