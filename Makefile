app_name=weinsteigfinance
build_dir=$(CURDIR)/build
package=$(build_dir)/$(app_name).tar.gz

.PHONY: help lint package clean

help:
	@echo "make lint     - PHP-Syntaxcheck"
	@echo "make package  - build/$(app_name).tar.gz zum Upload auf den Server"
	@echo "make clean    - build/ löschen"

lint:
	find . -path ./build -prune -o -name '*.php' -print0 | xargs -0 -n1 php -l

package: clean
	mkdir -p $(build_dir)/$(app_name)
	rsync -a --exclude='.git' --exclude='build' --exclude='.gitignore' \
		--exclude='Makefile' --exclude='vendor' --exclude='node_modules' \
		./ $(build_dir)/$(app_name)/
	tar -czf $(package) -C $(build_dir) $(app_name)
	@echo "fertig: $(package)"

clean:
	rm -rf $(build_dir)
