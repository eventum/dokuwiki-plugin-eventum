package_name := eventum

all:

dist:
	set -ex; \
	version=`awk '/date/{print $$2}' plugin.info.txt`; \
	package_dir=$(package_name)-$$version; \
	composer install --ignore-platform-reqs --no-dev; \
	rm -rf $(package_name); \
	install -d $(package_name)/XML; \
	git archive HEAD | tar -x -C $(package_name); \
	cp -p vendor/eventum/rpc/class.Eventum_RPC.php $(package_name); \
	cp -p vendor/pear-pear.php.net/XML_RPC/XML/RPC.php $(package_name)/XML; \
	rm $(package_name)/{.git*,composer.json,Makefile}; \
	tar -czf $(package_name)-$$version.tar.gz --remove-files $(package_name);
