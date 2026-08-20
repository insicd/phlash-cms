(function () {
  document.querySelectorAll('.cmt.c-below').forEach(function (el) {
    el.classList.add('collapsed');
    var btn = el.querySelector('.cmt-toggle');
    if (btn) btn.textContent = '+';
  });

  document.addEventListener('mousedown', function (e) {
    if (e.target.closest('[data-md]')) {
      e.preventDefault();
    }
  });

  document.addEventListener('click', function (e) {
    var tog = e.target.closest('.cmt-toggle');
    if (tog) {
      var box = tog.closest('.cmt');
      box.classList.toggle('collapsed');
      tog.textContent = box.classList.contains('collapsed') ? '+' : '−';
      return;
    }
    var reply = e.target.closest('.reply-btn');
    if (reply) {
      var form = document.getElementById(reply.getAttribute('data-target'));
      if (form) form.hidden = !form.hidden;
      return;
    }
    var mdBtn = e.target.closest('[data-md]');
    if (mdBtn) {
      e.preventDefault();
      var wrap = mdBtn.closest('.md-wrap');
      var ta = wrap && wrap.querySelector('textarea');
      if (ta) {
        mdApply(ta, mdBtn.getAttribute('data-md'));
      }
    }
  });

  document.addEventListener('keydown', function (e) {
    var ta = e.target;
    if (!ta || !ta.classList || !ta.classList.contains('md-input')) {
      return;
    }
    var key = (e.key || '').toLowerCase();
    var mod = e.metaKey || e.ctrlKey;
    if (!mod) {
      return;
    }
    if (key === 'b') {
      e.preventDefault();
      mdApply(ta, 'bold');
    } else if (key === 'i') {
      e.preventDefault();
      mdApply(ta, 'italic');
    } else if (key === 'k') {
      e.preventDefault();
      mdApply(ta, 'link');
    }
  });

  function mdApply(ta, action) {
    var val = ta.value;
    var start = ta.selectionStart;
    var end = ta.selectionEnd;
    var sel = val.slice(start, end);

    if (action === 'bold') {
      wrap(ta, '**', '**', 'grassetto');
    } else if (action === 'italic') {
      wrap(ta, '*', '*', 'corsivo');
    } else if (action === 'code') {
      wrap(ta, '`', '`', 'codice');
    } else if (action === 'link') {
      insertLink(ta, sel, start, end);
    } else if (action === 'h2') {
      prefixLines(ta, function () { return '## '; }, 'titolo');
    } else if (action === 'quote') {
      prefixLines(ta, function () { return '> '; }, 'citazione');
    } else if (action === 'ul') {
      prefixLines(ta, function () { return '- '; }, 'elemento');
    } else if (action === 'ol') {
      var n = 1;
      prefixLines(ta, function () { return (n++) + '. '; }, 'elemento');
    } else if (action === 'pre') {
      wrap(ta, '```\n', '\n```', 'codice');
    }
  }

  function wrap(ta, before, after, placeholder) {
    var start = ta.selectionStart;
    var end = ta.selectionEnd;
    var inner = ta.value.slice(start, end) || placeholder;
    setRange(ta, start, end, before + inner + after, start + before.length, start + before.length + inner.length);
  }

  function insertLink(ta, sel, start, end) {
    var urlLike = /^(https?:\/\/|mailto:)/i.test(sel.trim());
    var text;
    var url;
    var insert;
    var selFrom;
    var selTo;
    if (urlLike) {
      text = 'testo';
      url = sel.trim();
      insert = '[' + text + '](' + url + ')';
      selFrom = start + 1;
      selTo = selFrom + text.length;
    } else {
      text = sel || 'testo';
      url = 'https://';
      insert = '[' + text + '](' + url + ')';
      if (sel) {
        selFrom = start + text.length + 3;
        selTo = selFrom + url.length;
      } else {
        selFrom = start + 1;
        selTo = selFrom + text.length;
      }
    }
    setRange(ta, start, end, insert, selFrom, selTo);
  }

  function prefixLines(ta, makePrefix, placeholder) {
    var val = ta.value;
    var start = ta.selectionStart;
    var end = ta.selectionEnd;
    var lineStart = val.lastIndexOf('\n', start - 1) + 1;
    var lineEnd = val.indexOf('\n', end);
    if (lineEnd === -1) {
      lineEnd = val.length;
    }
    if (start === end && val.slice(lineStart, lineEnd) === '') {
      var p = makePrefix();
      setRange(ta, lineStart, lineEnd, p + placeholder, lineStart + p.length, lineStart + p.length + placeholder.length);
      return;
    }
    var block = val.slice(lineStart, lineEnd);
    var lines = block.split('\n');
    var out = lines.map(function (line) {
      var stripped = line.replace(/^>\s?/, '').replace(/^[-*]\s+/, '').replace(/^\d+\.\s+/, '').replace(/^##\s+/, '');
      if (stripped === '' && lines.length === 1) {
        stripped = placeholder;
      }
      return makePrefix() + stripped;
    }).join('\n');
    setRange(ta, lineStart, lineEnd, out, lineStart, lineStart + out.length);
  }

  function setRange(ta, from, to, text, selStart, selEnd) {
    ta.value = ta.value.slice(0, from) + text + ta.value.slice(to);
    ta.setSelectionRange(selStart, selEnd);
    ta.focus();
  }

  var picker = document.getElementById('icon-picker');
  if (picker) {
    var pickerTarget = null;
    var filter = document.getElementById('icon-filter');
    var choices = picker.querySelectorAll('.icon-choice');

    function safeIcon(name) {
      name = String(name || '').toLowerCase().replace(/^fa-(solid\s+)?/, '').replace(/^fa-/, '').trim();
      return /^[a-z0-9-]{1,48}$/.test(name) ? name : '';
    }

    function setIconField(field, name) {
      name = safeIcon(name);
      var input = field.querySelector('.icon-name');
      var preview = field.querySelector('.icon-preview');
      if (input && document.activeElement !== input) input.value = name;
      if (preview) {
        preview.textContent = '';
        if (name) {
          var i = document.createElement('i');
          i.className = 'fa-solid fa-' + name;
          preview.appendChild(i);
        }
      }
    }

    document.addEventListener('click', function (e) {
      var openBtn = e.target.closest('.icon-pick-btn');
      if (openBtn) {
        e.preventDefault();
        pickerTarget = openBtn.closest('.icon-field');
        picker.hidden = false;
        if (filter) filter.focus();
        return;
      }
      if (e.target.closest('.icon-picker-close')) {
        picker.hidden = true;
        pickerTarget = null;
        return;
      }
      var choice = e.target.closest('.icon-choice');
      if (choice && pickerTarget) {
        setIconField(pickerTarget, choice.getAttribute('data-icon') || '');
        picker.hidden = true;
        pickerTarget = null;
      }
    });

    document.querySelectorAll('.icon-name').forEach(function (input) {
      input.addEventListener('input', function () {
        var field = input.closest('.icon-field');
        if (field) {
          var preview = field.querySelector('.icon-preview');
          var name = safeIcon(input.value);
          if (preview) {
            preview.textContent = '';
            if (name) {
              var i = document.createElement('i');
              i.className = 'fa-solid fa-' + name;
              preview.appendChild(i);
            }
          }
        }
      });
    });

    if (filter) {
      filter.addEventListener('input', function () {
        var q = filter.value.toLowerCase().trim();
        choices.forEach(function (btn) {
          var id = btn.getAttribute('data-icon') || '';
          btn.classList.toggle('is-hidden', q !== '' && id.indexOf(q) === -1);
        });
      });
    }
  }
})();
