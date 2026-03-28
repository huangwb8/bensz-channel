import assert from 'node:assert/strict';
import test from 'node:test';

import {
    collapseSiblingTocNodes,
    expandTocNodePath,
    initArticleToc,
    isTocNodeExpanded,
    scrollToTocTarget,
    setTocNodeExpanded,
    targetIdFromTocLink,
    toggleTocNode,
} from './article-toc.js';

class FakeElement
{
    constructor({ dataset = {}, attributes = {} } = {})
    {
        this.dataset = { ...dataset };
        this.attributes = new Map(Object.entries(attributes));
        this.children = [];
        this.parentElement = null;
        this.listeners = new Map();
    }

    appendChild(child)
    {
        child.parentElement = this;
        this.children.push(child);

        return child;
    }

    setAttribute(name, value)
    {
        this.attributes.set(name, String(value));
    }

    getAttribute(name)
    {
        return this.attributes.get(name) ?? null;
    }

    addEventListener(type, handler)
    {
        this.listeners.set(type, handler);
    }

    dispatch(type, event)
    {
        const listener = this.listeners.get(type);

        if (listener) {
            listener(event);
        }
    }

    contains(element)
    {
        let current = element;

        while (current) {
            if (current === this) {
                return true;
            }

            current = current.parentElement;
        }

        return false;
    }

    closest(selector)
    {
        let current = this;

        while (current) {
            if (matchesSelector(current, selector)) {
                return current;
            }

            current = current.parentElement;
        }

        return null;
    }

    querySelectorAll(selector)
    {
        const matches = [];

        const visit = (element) => {
            if (matchesSelector(element, selector)) {
                matches.push(element);
            }

            element.children.forEach((child) => {
                visit(child);
            });
        };

        visit(this);

        return matches;
    }
}

const selectorDatasetRequirements = (selector) => Array.from(
    selector.matchAll(/\[data-([a-z-]+)(?:="([^"]+)")?\]/g),
    ([, key, value]) => ({
        key: key.replace(/-([a-z])/g, (_, letter) => letter.toUpperCase()),
        value: value ?? null,
    }),
);

const matchesSelector = (element, selector) => {
    const requirements = selectorDatasetRequirements(selector);

    return requirements.every(({ key, value }) => {
        if (! Object.prototype.hasOwnProperty.call(element.dataset, key)) {
            return false;
        }

        if (value === null) {
            return true;
        }

        return element.dataset[key] === value;
    });
};

const createTocNode = ({
    href,
    collapsible = false,
    expanded = false,
    childNodes = [],
} = {}) => {
    const node = new FakeElement({
        dataset: {
            tocNode: '',
            ...(collapsible ? {
                tocCollapsible: 'true',
                tocExpanded: expanded ? 'true' : 'false',
            } : {}),
        },
    });

    const link = node.appendChild(new FakeElement({
        dataset: { tocLink: '' },
        attributes: { href: href ?? '#section' },
    }));

    let toggle = null;
    let branch = null;

    if (collapsible) {
        toggle = node.appendChild(new FakeElement({
            dataset: { tocToggle: '' },
            attributes: { 'aria-expanded': expanded ? 'true' : 'false' },
        }));
        branch = node.appendChild(new FakeElement({
            dataset: { tocBranch: '' },
            attributes: { 'aria-hidden': 'false' },
        }));

        const inner = branch.appendChild(new FakeElement());

        childNodes.forEach((childNode) => {
            inner.appendChild(childNode);
        });
    }

    return { node, link, toggle, branch };
};

const createTocContainer = (...nodes) => {
    const container = new FakeElement({ dataset: { articleToc: '' } });
    const list = container.appendChild(new FakeElement({ dataset: { tocList: '' } }));

    nodes.forEach((node) => {
        list.appendChild(node);
    });

    return container;
};

const dispatchClick = (container, target) => {
    const event = {
        target,
        prevented: false,
        stopped: false,
        preventDefault() {
            this.prevented = true;
        },
        stopPropagation() {
            this.stopped = true;
        },
    };

    container.dispatch('click', event);

    return event;
};

test('setTocNodeExpanded syncs expanded state onto toggle and branch', () => {
    const { node, toggle, branch } = createTocNode({ collapsible: true, expanded: false });

    setTocNodeExpanded(node, true);

    assert.equal(node.dataset.tocExpanded, 'true');
    assert.equal(toggle.getAttribute('aria-expanded'), 'true');
    assert.equal(branch.getAttribute('aria-hidden'), 'false');
});

test('collapseSiblingTocNodes closes expanded sibling branches recursively', () => {
    const nestedChild = createTocNode({ collapsible: true, expanded: true }).node;
    const current = createTocNode({ collapsible: true, expanded: false }).node;
    const sibling = createTocNode({
        collapsible: true,
        expanded: true,
        childNodes: [nestedChild],
    }).node;
    const parent = new FakeElement();

    parent.appendChild(current);
    parent.appendChild(sibling);

    collapseSiblingTocNodes(current);

    assert.equal(isTocNodeExpanded(current), false);
    assert.equal(isTocNodeExpanded(sibling), false);
    assert.equal(isTocNodeExpanded(nestedChild), false);
});

test('expandTocNodePath opens each collapsible ancestor in the active path', () => {
    const grandChild = createTocNode({ href: '#deep-node' }).node;
    const child = createTocNode({
        collapsible: true,
        expanded: false,
        childNodes: [grandChild],
    }).node;
    const root = createTocNode({
        collapsible: true,
        expanded: false,
        childNodes: [child],
    }).node;

    expandTocNodePath(grandChild);

    assert.equal(isTocNodeExpanded(root), true);
    assert.equal(isTocNodeExpanded(child), true);
});

test('toggleTocNode behaves like an accordion for sibling branches', () => {
    const first = createTocNode({ collapsible: true, expanded: false }).node;
    const second = createTocNode({ collapsible: true, expanded: true }).node;
    const parent = new FakeElement();

    parent.appendChild(first);
    parent.appendChild(second);

    assert.equal(toggleTocNode(first), true);
    assert.equal(isTocNodeExpanded(first), true);
    assert.equal(isTocNodeExpanded(second), false);
    assert.equal(toggleTocNode(first), false);
    assert.equal(isTocNodeExpanded(first), false);
});

test('targetIdFromTocLink decodes hash identifiers', () => {
    const { link } = createTocNode({ href: '#section-%E4%B8%AD' });

    assert.equal(targetIdFromTocLink(link), 'section-中');
});

test('scrollToTocTarget scrolls matching heading and updates hash', () => {
    const { link } = createTocNode({ href: '#details' });
    const calls = [];
    const historyCalls = [];
    const target = {
        id: 'details',
        scrollIntoView(options) {
            calls.push(options);
        },
    };

    const result = scrollToTocTarget(link, {
        documentRoot: {
            getElementById(id) {
                return id === 'details' ? target : null;
            },
        },
        historyObject: {
            replaceState(...args) {
                historyCalls.push(args);
            },
        },
        reducedMotion: false,
    });

    assert.equal(result, true);
    assert.deepEqual(calls, [{ behavior: 'smooth', block: 'start' }]);
    assert.deepEqual(historyCalls, [[null, '', '#details']]);
});

test('initArticleToc collapses nested branches only after enhancement is enabled', () => {
    const root = createTocNode({
        href: '#overview',
        collapsible: true,
        childNodes: [createTocNode({ href: '#details' }).node],
    });
    const container = createTocContainer(root.node);

    assert.equal(root.branch.getAttribute('aria-hidden'), 'false');

    initArticleToc(container, {
        documentRoot: { getElementById() { return null; } },
        historyObject: null,
        reducedMotion: true,
        initialHash: '',
    });

    assert.equal(container.dataset.tocEnhanced, 'true');
    assert.equal(root.branch.getAttribute('aria-hidden'), 'true');
    assert.equal(root.toggle.getAttribute('aria-expanded'), 'false');
});

test('initArticleToc expands a branch when its link is clicked and folds it with the toggle button', () => {
    const historyCalls = [];
    const scrollCalls = [];
    const target = {
        id: 'overview',
        scrollIntoView(options) {
            scrollCalls.push(options);
        },
    };
    const root = createTocNode({
        href: '#overview',
        collapsible: true,
        childNodes: [createTocNode({ href: '#details' }).node],
    });
    const container = createTocContainer(root.node);

    initArticleToc(container, {
        documentRoot: {
            getElementById(id) {
                return id === 'overview' ? target : null;
            },
        },
        historyObject: {
            replaceState(...args) {
                historyCalls.push(args);
            },
        },
        reducedMotion: false,
        initialHash: '',
    });

    const linkEvent = dispatchClick(container, root.link);

    assert.equal(linkEvent.prevented, true);
    assert.equal(isTocNodeExpanded(root.node), true);
    assert.deepEqual(scrollCalls, [{ behavior: 'smooth', block: 'start' }]);
    assert.deepEqual(historyCalls, [[null, '', '#overview']]);

    const toggleEvent = dispatchClick(container, root.toggle);

    assert.equal(toggleEvent.prevented, true);
    assert.equal(toggleEvent.stopped, true);
    assert.equal(isTocNodeExpanded(root.node), false);
    assert.deepEqual(historyCalls, [[null, '', '#overview']]);
});

test('initArticleToc expands the ancestor path for an initial hash', () => {
    const grandChild = createTocNode({ href: '#deep-node' }).node;
    const child = createTocNode({
        href: '#details',
        collapsible: true,
        childNodes: [grandChild],
    });
    const root = createTocNode({
        href: '#overview',
        collapsible: true,
        childNodes: [child.node],
    });
    const container = createTocContainer(root.node);

    initArticleToc(container, {
        documentRoot: { getElementById() { return null; } },
        historyObject: null,
        reducedMotion: true,
        initialHash: '#deep-node',
    });

    assert.equal(isTocNodeExpanded(root.node), true);
    assert.equal(isTocNodeExpanded(child.node), true);
});

test('initArticleToc falls back to native anchor behavior when the target heading is missing', () => {
    const root = createTocNode({
        href: '#missing',
        collapsible: true,
        childNodes: [createTocNode({ href: '#details' }).node],
    });
    const container = createTocContainer(root.node);

    initArticleToc(container, {
        documentRoot: { getElementById() { return null; } },
        historyObject: null,
        reducedMotion: true,
        initialHash: '',
    });

    const event = dispatchClick(container, root.link);

    assert.equal(event.prevented, false);
    assert.equal(isTocNodeExpanded(root.node), true);
});
