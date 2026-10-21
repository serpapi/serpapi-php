.PHONY: all clean install lint test readme demo

# Default target
all: install readme lint test

# Clean up the project
clean:
	rm -rf vendor/

# Install the necessary libraries and dependencies
install:
	composer install --prefer-dist --no-progress

# Run PSR12 lint
lint:
	vendor/bin/phpcs

# Run the tests
test:
	vendor/bin/phpunit -c phpunit.xml

# Generate README from ERB template
readme:
	erb -T '-' README.md.erb > README.md

# Run the demo scripts against the live API (requires SERPAPI_KEY)
demo:
	@for file in demo/*.php; do \
		echo "running $$file"; \
		php $$file || exit 1; \
	done
