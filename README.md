# bed.php

REST-oriented PHP micro-framework. The idea behind it is to provide the minimum
amount of functionality that allows writing REST APIs comfortably, without
compromising performance or conditioning too much system-specific architecture
decisions. To accomplish that, the framework puts together components common to
virtually every REST API, and no more. In turn, the use of PHP native features
is encouraged, as they are many times sufficient for most of the cases, and very
well documented.

In addition, a bunch of utility functions is provided, each on a separate file,
so that only what is really needed needs to be imported.

For both the core functionality and the additional tools, you are allowed and
encouraged to select only the parts your application really needs. There is no
imposition to this regard coming from the framework.

Requires PHP >= 7.
