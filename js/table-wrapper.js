// Automatically wrap all tables with scrollable containers
document.addEventListener('DOMContentLoaded', function() {
	const appContainer = document.querySelector('.app-weinsteigfinance');
	if (!appContainer) return;

	// Find all tables
	appContainer.querySelectorAll('table').forEach(table => {
		// Skip if already wrapped
		if (table.parentElement && table.parentElement.classList.contains('table-wrapper')) {
			return;
		}

		// Create wrapper
		const wrapper = document.createElement('div');
		wrapper.className = 'table-wrapper';

		// Insert wrapper before table and move table into wrapper
		table.parentElement.insertBefore(wrapper, table);
		wrapper.appendChild(table);
	});
});
