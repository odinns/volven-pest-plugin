# AGENTS.md

This is a Pest plugin, not a Laravel package.

Rules:

- no Laravel Testbench
- keep the package deterministic
- do not duplicate Pint, PHPStan, Psalm, Rector, Pest `arch()`, or Psalm-style checks
- failure output must follow the Vølven finding model
- old debt may be baselined
- new debt should fail CI
- keep APIs explicit and readable

Use Odinn's direct technical voice for docs, comments, and examples. If the wording sounds like a SaaS slide, delete it and try again.
