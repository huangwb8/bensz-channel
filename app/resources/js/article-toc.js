const hasDatasetKey = (element, key) => Boolean(
    element?.dataset && Object.prototype.hasOwnProperty.call(element.dataset, key),
);

const elementChildren = (element) => Array.from(element?.children ?? []);

const findDirectChild = (element, key) => elementChildren(element).find((child) => hasDatasetKey(child, key)) ?? null;

const findDirectTocNodes = (element) => elementChildren(element).filter((child) => hasDatasetKey(child, 'tocNode'));

export const findParentTocNode = (element) => {
    let current = element;

    while (current) {
        if (hasDatasetKey(current, 'tocNode')) {
            return current;
        }

        current = current.parentElement ?? null;
    }

    return null;
};

export const isTocNodeCollapsible = (node) => node?.dataset?.tocCollapsible === 'true';

export const isTocNodeExpanded = (node) => node?.dataset?.tocExpanded === 'true';

export const tocToggleForNode = (node) => findDirectChild(node, 'tocToggle');

export const tocBranchForNode = (node) => findDirectChild(node, 'tocBranch');

export const tocChildNodes = (node) => {
    const branch = tocBranchForNode(node);
    const inner = branch ? elementChildren(branch)[0] ?? null : null;

    return findDirectTocNodes(inner);
};

export const setTocNodeExpanded = (node, expanded) => {
    if (! isTocNodeCollapsible(node)) {
        return;
    }

    node.dataset.tocExpanded = expanded ? 'true' : 'false';

    const toggle = tocToggleForNode(node);

    if (toggle) {
        toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    }

    const branch = tocBranchForNode(node);

    if (branch) {
        branch.setAttribute('aria-hidden', expanded ? 'false' : 'true');
    }
};

export const collapseTocNode = (node) => {
    tocChildNodes(node).forEach((childNode) => {
        collapseTocNode(childNode);
    });

    if (isTocNodeCollapsible(node)) {
        setTocNodeExpanded(node, false);
    }
};

export const collapseSiblingTocNodes = (node) => {
    const parent = node?.parentElement;

    if (! parent) {
        return;
    }

    findDirectTocNodes(parent).forEach((candidate) => {
        if (candidate === node) {
            return;
        }

        collapseTocNode(candidate);
    });
};

export const expandTocNodePath = (node) => {
    let current = node;

    while (current) {
        if (isTocNodeCollapsible(current)) {
            setTocNodeExpanded(current, true);
        }

        current = findParentTocNode(current.parentElement);
    }
};

export const expandTocNode = (node) => {
    if (! isTocNodeCollapsible(node)) {
        return;
    }

    collapseSiblingTocNodes(node);
    setTocNodeExpanded(node, true);
};

export const toggleTocNode = (node) => {
    if (! isTocNodeCollapsible(node)) {
        return false;
    }

    if (isTocNodeExpanded(node)) {
        collapseTocNode(node);

        return false;
    }

    expandTocNode(node);

    return true;
};

export const targetIdFromTocLink = (link) => {
    const href = link?.getAttribute?.('href');

    if (typeof href !== 'string' || ! href.startsWith('#') || href.length === 1) {
        return null;
    }

    return decodeURIComponent(href.slice(1));
};

export const scrollToTocTarget = (link, {
    documentRoot = typeof document === 'undefined' ? null : document,
    historyObject = typeof window === 'undefined' ? null : window.history,
    reducedMotion = typeof window !== 'undefined'
        && typeof window.matchMedia === 'function'
        && window.matchMedia('(prefers-reduced-motion: reduce)').matches,
} = {}) => {
    const targetId = targetIdFromTocLink(link);

    if (! targetId || ! documentRoot?.getElementById) {
        return false;
    }

    const target = documentRoot.getElementById(targetId);

    if (! target?.scrollIntoView) {
        return false;
    }

    target.scrollIntoView({
        behavior: reducedMotion ? 'auto' : 'smooth',
        block: 'start',
    });

    if (historyObject?.replaceState) {
        historyObject.replaceState(null, '', `#${target.id}`);
    }

    return true;
};

const closestWithDataset = (element, key) => {
    if (! element) {
        return null;
    }

    if (typeof element.closest === 'function') {
        return element.closest(`[data-${key.replace(/[A-Z]/g, (match) => `-${match.toLowerCase()}`)}]`);
    }

    return findParentTocNode(element);
};

export const initArticleToc = (container, options = {}) => {
    if (! container || container.dataset.tocReady === 'true' || typeof container.addEventListener !== 'function') {
        return;
    }

    const documentRoot = options.documentRoot ?? (typeof document === 'undefined' ? null : document);
    const historyObject = options.historyObject ?? (typeof window === 'undefined' ? null : window.history);
    const reducedMotion = options.reducedMotion ?? (
        typeof window !== 'undefined'
        && typeof window.matchMedia === 'function'
        && window.matchMedia('(prefers-reduced-motion: reduce)').matches
    );
    const syncHashPath = (hash) => {
        if (! hash || typeof container.querySelectorAll !== 'function') {
            return;
        }

        const link = Array.from(container.querySelectorAll('[data-toc-link]')).find(
            (candidate) => candidate.getAttribute('href') === hash,
        );

        if (! link) {
            return;
        }

        const node = findParentTocNode(link);

        if (! node) {
            return;
        }

        expandTocNodePath(node);

        if (isTocNodeCollapsible(node)) {
            setTocNodeExpanded(node, true);
        }
    };

    if (typeof container.querySelectorAll === 'function') {
        container.querySelectorAll('[data-toc-node][data-toc-collapsible="true"]').forEach((node) => {
            setTocNodeExpanded(node, false);
        });
    }

    syncHashPath(options.initialHash ?? (typeof window === 'undefined' ? '' : window.location.hash));
    container.dataset.tocEnhanced = 'true';

    container.addEventListener('click', (event) => {
        const toggle = closestWithDataset(event.target, 'tocToggle');

        if (toggle && typeof container.contains === 'function' && container.contains(toggle)) {
            const node = findParentTocNode(toggle);

            if (node) {
                event.preventDefault();
                event.stopPropagation();
                toggleTocNode(node);
            }

            return;
        }

        const link = closestWithDataset(event.target, 'tocLink');

        if (! link || (typeof container.contains === 'function' && ! container.contains(link))) {
            return;
        }

        const node = findParentTocNode(link);

        if (node && isTocNodeCollapsible(node) && ! isTocNodeExpanded(node)) {
            expandTocNode(node);
        }

        if (node) {
            expandTocNodePath(node);
        }

        const didScroll = scrollToTocTarget(link, {
            documentRoot,
            historyObject,
            reducedMotion,
        });

        if (didScroll) {
            event.preventDefault();
        }
    });

    if (typeof window !== 'undefined' && typeof window.addEventListener === 'function') {
        window.addEventListener('hashchange', () => {
            syncHashPath(window.location.hash);
        });
    }

    container.dataset.tocReady = 'true';
};

export const initArticleTocs = (root = typeof document === 'undefined' ? null : document) => {
    if (! root?.querySelectorAll) {
        return;
    }

    root.querySelectorAll('[data-article-toc]').forEach((container) => {
        initArticleToc(container);
    });
};
