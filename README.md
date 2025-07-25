**PROJECT TITLE:** Rothamsted FarmOS Modules

**PROJECT OVERVIEW:**

This project contains custom FarmOS Modules for [Rothamsted Research](https://www.rothamsted.ac.uk/).

These customised [Rothamsted FarmOS Modules](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/tree/2.x/modules) are extensions to FarmOS that are designed to capture research quality farm management and field experiment data. The system in in active daily use on all three of Rothamsted’s Field Experimental Stations (Harpenden/ Woburn, Brooms Barn and North Wyke).

Currently there are eight modules designed to support the implementation of two key features. The key features are:

1. [**Rothamsted Quick Forms**](modules/farm_rothamsted_quick) which enable standardised data collection for farm and field management activities (drilling, cultivations, pesticide and nutrient inputs, etc)
2. **[Rothamsted Experiment Module](modules/farm_rothamsted_experiment_research)** which is used to record ISA and MIAPPE compliant metadata about farm and field experiments.

**MAINTAINERS:**

This project is maintained by

- [**Michael Stenta**](https://github.com/mstenta) **(FarmOS).** FarmOS Founder and Lead Maintainer.
- [**Paul Weidner**](https://github.com/paul121) **(FarmOS).** FarmOS Co-Maintainer; Lead Maintainer for the Rothamsted Modules
- [**Richard Ostler**](https://github.com/richardostler) **(Rothamsted Research).** Head of Agri-Informatics at Rothamsted; Principal Data Scientist for the Rothamsted Modules.
- [**Dr Aislinn Pearson**](https://github.com/aislinnpearson) **(Rothamsted Research).** Agricultural Ecologist; Product Owner for the Rothamsted Modules

Contributors:

- [**Barnaby Norman**](https://github.com/barnabynorman)**.** Contributor to the Rothamsted Quick Forms

**KEY FEATURE:** [**ROTHAMSTED QUICK FORMS**](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/tree/2.x/modules/farm_rothamsted_quick/src/Plugin/QuickForm)

The Rothamsted Quick forms are set of data collection forms designed to standardise the way data is captured and stored in FarmOS. The current list of quick forms includes:

- **Commercial Plant Assets Quick** **Form**: a form for creating commercial plant assets with standardised names and descriptors. This is designed for use with field scale and commercial crops only.
- **Drilling Quick Form:** standardised data capture for planting of arable crops (including cover crops), permanent pasture, and environmental land management schemes. Can be extended to cover the planting of tree and energy crops such as willow and miscanthus but is not designed for this purpose. Documents drilling events at field and plot scale
- **Spraying Quick Form:** standardised data capture for pesticide applications. Can be extended to include irrigation but is not designed for this purpose. Documents spraying events at field and plot scale
- **Field Operations Quick Form:** standardised data capture for cultivations (ploughing, rolling, etc), grassland management (mowing, rowing up, etc) and other operations (hedge trimming, hand weeding, etc) Documents field operations at field and plot scale
- **Fertiliser, Compost and Manure Quick Form:** standardised data capture for nutrient inputs in arable systems. This quick form differentiates between inorganic inputs (e.g. nitrogen-based fertilisers) and organic inputs (compost, farmyard manure, etc). Documents nutrition input events at field and plot scale
- **Harvest (Combine and Forage Harvesters) Quick Form:** A quick form designed to capture data collected by combine and grass harvesters (e.g. harvest maps produced by the combine or operational data from a grass harvester). Documents combine harvest events at field and plot scale
- **Harvest (Trailer and Bale Weights) Quick Form:** A quick form designed to capture the data about actual harvested data (e.g. trailer weights in arable systems, or number of bales produced in grassland systems). Documents off-takes at field scale, and can be extended to plot scale but is not designed for this purpose (data at plot scale is generally recorded as a measurement or response variable).

**KEY FEATURE: ROTHAMSTED EXPERIMENT MODULES**

The Rothamsted Experiment Module is a set of six custom [entity types](https://www.drupal.org/docs/7/api/entity-api/an-introduction-to-entities) that are used to store data about different elements of an experiment. Each module is designed to align with the ISA and MIAPPE standards. The current list of entity types includes:

- **Researchers:** metadata that describes the individual people who are associated with the Research. Researchers can be named on Research Programs, Proposals and Experiments. Researchers who are in the system as Statisticians can be named on Designs.
- **Research Programs:** metadata that describes the Research Program that funds the research. It is primarily designed for government and philanthropic funding but can also record commercial research contracts. Experiments and Proposals can be linked to one or more Research Programs.
- **Proposals:** metadata that describes proposed experiments (similar to the pre-registration of studies in the medical sciences). Proposals are designed to be reviewed by a panel of internal reviewers. Can be extended to include external reviewers but is not designed for this purpose. Proposals can be linked to one or more experiment, one or more designs and one or more study plans.
- **Experiments:** metadata that describes an experiment. An experiment can have one or more designs and one or more study plans. They can also be linked to multiple Researchers and multiple Research Programs. An experiment is an Investigation in the Investigation – Study – Assay (ISA) Framework.
- **Designs:** metadata that describes the design of an experiment, including the statistical randomisation and management plan. A design can be linked to multiple experiments (for example if the same statistical design is used in multiple field trials) and can have multiple study plans (for example, in the same design is used for several years in a row). One or mor researchers can be named on a Design, as long as each of those Researchers in named in the system as a statistician.
- **Study Plans:** metadata that describes a specific study associated with a Design. For example, for an arable field trial in the UK, a study plan is usually used to document a single year that runs from seedbed preparation (late autumn or early spring) through to harvest (mid to late summer). A Design that is replicated over multiple years would have multiple study plans. However, the system is designed to be flexible enough to cover a single design that covers multiple years (for example, if the experiment is studying perennial crops such as willow or lucerne). Design replicates which are run concurrently (e.g. the same experiment replicated in two different locations in the same season) can be documented as a single study plan or two separate study plans linked to the same design. Study plans can include a full list of ontologically described variables which can be assigned to individual plots. Users also have the option to add plot geometry as well as a one or more land assets categorised as an Experiment Boundary. Other assets such as sensors and structures can also be linked to these plans. The plans will then collate all logs associated with the assets linked to the Plan. The Rothamsted Quick Forms are used to document management actions at both plot scale (treatments) and across the experiment boundary (baseline management).

**ROTHAMSTED MODULES**

FarmOS is built using Drupal, which is a modular web content management service (WMS). Drupal allows users to combine different modules to create a website that it tailored to their specific needs. FarmOS both uses existing Drupal modules and creates new custom modules specific to FarmOS. The Rothamsted implementation of FarmOS uses these modules to deliver the core services and then extends them to enable the above features but adding eight additional modules which are specific to Rothamsted. The Rothamsted modules include:

- **Rothamsted Quick Forms (**[farm_rothamsted_quick](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/tree/2.x/modules/farm_rothamsted_quick"%20\o%20"farm_rothamsted_quick)**):** A module which contains all of the Rothamsted Quick Forms
- **Rothamsted Experiment Modules:**
  - **Study Plans** ([farm_rothamsted_experiment](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/tree/2.x/modules/farm_rothamsted_experiment"%20\o%20"farm_rothamsted_experiment)): a module specific to the study plans, which also adds plots as a new asset type and ‘_experiment boundary_’ as a new category for land assets. Users can upload ontologically tagged variables using a .csv uploader, and this uploader is also used to tag plots with their treatments. Users also have the option to upload a GeoJSON with the plot. These were previously called Experiment Plans, but were renamed as Study Plans when we adopted the MIAPPE and ISA Standards.
  - **Research Metadata** ([farm_rothamsted_experiment_research](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/tree/2.x/modules/farm_rothamsted_experiment_research"%20\o%20"farm_rothamsted_experiment_research)): Drupal entities which capture MIAPPE and ISA compliant metadata for Research Programs, Proposals, Experiments and Designs.
  - **Researcher Metadata (**[farm_rothamsted_researcher](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/tree/2.x/modules/farm_rothamsted_researcher"%20\o%20"farm_rothamsted_researcher)): A Drupal entity that captures MIAPPE and ISA compliant metadata for the Researchers associated with the Research Metadata.
- **Roles:** Customised FarmOS roles for Rothamsted staff. This module replaces the three existing FarmOS roles (Manager, Worker and Viewer) and adds five new roles:
  - **Manager** is replaced by **Farm Manager**.
  - **Viewer** is replaced by **Farm Viewer**.
  - **Worker** is replaced by two new roles:

1. **Operator (Basic)** which has limited functionality and is specifically designed for new starters and temporary staff, and
2. **Operator (Advanced)** which is designed for operational staff trained in the use of the system.
    - **Farm Data Administrator** is a new role that is designed to give high level permissions to our Research Data Stewardship team;
    - **Research Leads** is a new role designed for the Senior Scientists overseeing experiments;
    - **Research Editors** is a new role designed for Technicians, Post-Doctoral Researchers and PhD Students associated with experiments;
    - **Research Reviewers** is a new role designed to support our internal scientific review processes; and
    - **Restricted Viewers** is an new role which allows people who do not work at Rothamsted to view the data associated with experiments that they are named on.

- **Export data** ([farm_rothamsted_export](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/tree/2.x/modules/farm_rothamsted_export"%20\o%20"farm_rothamsted_export)) This module exports allows users to export the raw data associated with the Rothamsted Experiment Module as .csv files packaged in a .zip file. It also extends the export functionality in FarmOS core to so users to export all the raw data associated with multiple assets and/or multiple logs using the same .zip format for packaged .csv files.
- **Comments** ([farm_rothamsted_comment](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/tree/2.x/modules/farm_rothamsted_comment"%20\o%20"farm_rothamsted_comment)): This module allows users to record comments on the Research Module entities. It also gives reviewers special permission to comment on a Proposal and then mark that Proposal as Reviewed with a name, date and timestamp. It also does e-mail notifications for comments. This module only adds comments to Research module, extending the core comment functionality FarmOS.
- **E-mail Notifications** ([farm_rothamsted_notification](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/tree/2.x/modules/farm_rothamsted_notification"%20\o%20"farm_rothamsted_notification)): This module controls the e-mail notifications sent to staff who are named on specific Research Programs, Proposals, Experiment and Designs.
- **Dashboard customisation** ([farm_rothamsted_dashboard](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/tree/2.x/modules/farm_rothamsted_dashboard"%20\o%20"farm_rothamsted_dashboard)): This module customised the Rothamsted dashboard, and adds specific search functionality to make it easer to find experiments, plant assets and field locations.

**OUR CONTRIBUTIONS TO THE FARMOS COMMUNITY:**

When Rothamsted request a feature which the FarmOS maintainers identify as having value to the wider FarmOS community, Rothamsted pay for the development of that feature but it is released as part of the core FarmOS software package so it can be used by the wider community. This is a two-way process, with Rothamsted benefitting from many community developments. The Farm Calendar is a community feature that our Farm team particularly appreciated. Others such as the Plans and Inventories we are actively tailoring to suit our internal processes.

Any Rothamsted specific feature requests which depend on or are relevant to the FarmOS community are [tagged](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues?q=is%3Aissue%20label%3A%22FarmOS%20Core%22&page=1) and added to our [issue queue](https://github.com/orgs/Rothamsted-Ecoinformatics/projects/20/views/1) (see a full list [here](https://github.com/Rothamsted-Ecoinformatics/farm_rothamsted/issues?q=is%3Aissue%20label%3A%22FarmOS%20Core%22&page=1)). Examples of our contributions to FarmOS core include printing maps directly from the map viewer, co-funding the John Deere integration, funding the comment functionality on FarmOS assets/ logs and contributing to the comment and data export functionality.

We are actively seeking funding to make all our modules available to the wider community.

**DOCUMENTATION:**

As this project is currently only deployed internally at Rothamsted, all documentation is saved in an [Electronic Research Notebook](https://uk-mynotebook.labarchives.com/share/Rothamsted%2520Research%2520Resources/Mi42fDI1NzAxLzIvVHJlZU5vZGUvMzIxMjAxNjEzNXw2LjY=) that is only accessible to staff. This documentation can be made available to those outside Rothamsted at request.

Add RDSQ thing.

Add sperate file for folder structure

**ISSUE QUEUE:**

New features and requests from users are logged and prioritised in the associated [Github Project](https://github.com/orgs/Rothamsted-Ecoinformatics/projects/20). Aislinn to add README

**STANDARDS AND COMPLIANCE:**

**Red Tractor:** All the Rothamsted Quick Forms are Red Tractor Compliant, and data collected is used in our own Red Tractor audits.

**Investigation Study Assay (ISA):** The Rothamsted Experiment Module was designed to align with the Investigation Study Assay (ISA) Framework

**Minimum Information About Plant Phenotyping Experiments (MIAPPE):** The Rothamsted Experiment Module is designed to align with the MIAPPE metadata standards, which is also based on the ISA Framework.

**FUNDERS:**

The initial build of the Research Module and Rothamsted Quick Forms was funded by the BBSRC through an Institute Transformation Grant. The ongoing maintenance is funded by the BBSRC and the Lawes Agricultural Trust.

**LICENSE:**

This project inherits a copy left [GNU General Public License, version 2 (GPL v2.0)](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html) license from FarmOS and Drupal.

Under the terms of this license, you are free to use the code in this repository for any purpose, but if you incorporate that code into products which are released publicly, you must release the software under the same GPL v2.0 terms.

Should we list any linked projects here?

**INSTALLATION AND SUPPORT:**

These modules are custom add-ons for [farmOS](http://drupal.org/project/farm). The modules have been designed for internal use at Rothamsted only; currently there is no support for module being used in other production use cases.

**CONTACT:**

The software is in active development. If you would like to partner with us in a project, or for more information about this project please contact Richard Ostler ([richard.ostler@rothamsted.ac.uk](mailto:richard.ostler@rothamsted.ac.uk)) or Aislinn Pearson ([aislinn.pearson@rothamsted.ac.uk](mailto:aislinn.pearson@rothamsted.ac.uk)).

For general FarmOS enquiries please go via the [maintainers](https://farmos.org/community/maintainers/) named on the FarmOS webpages.
