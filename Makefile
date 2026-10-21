.PHONY: all clean install lint analyse test readme demo

# Default target
all: install readme lint analyse test

# Clean up the project
clean:
	rm -rf vendor/

# Install the necessary libraries and dependencies
install:
	composer install --prefer-dist --no-progress

# Run PSR12 lint
lint:
	vendor/bin/phpcs

# Run static analysis
analyse:
	vendor/bin/phpstan analyse --no-progress --memory-limit=512M

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
