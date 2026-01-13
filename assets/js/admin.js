jQuery(document).ready(function ($) {
  // Helper function for REST API calls
  function restApiCall(endpoint, method = 'GET', data = null) {
    return $.ajax({
      url: ChatStoryAjax.rest_url + endpoint,
      method: method,
      beforeSend: function(xhr) {
        xhr.setRequestHeader('X-WP-Nonce', ChatStoryAjax.rest_nonce);
      },
      data: (method === 'GET' || method === 'DELETE') ? data : JSON.stringify(data),
      contentType: (method === 'POST' || method === 'PUT') ? 'application/json' : undefined,
      dataType: 'json'
    });
  }

  // ==================== CHARACTERS PAGE ====================
  if ($("#characters-table").length) {
    loadCharacters();

    $("#add-character-btn").on("click", function () {
      showCharacterForm();
    });

    $("#cancel-character-btn").on("click", function () {
      hideCharacterForm();
    });

    $("#character-edit-form").on("submit", function (e) {
      e.preventDefault();
      saveCharacter();
    });

    $(document).on("click", ".edit-character", function () {
      const id = $(this).data("id");
      editCharacter(id);
    });

    $(document).on("click", ".delete-character", function () {
      const id = $(this).data("id");
      if (confirm("Are you sure you want to delete this character?")) {
        deleteCharacter(id);
      }
    });

    // Import characters
    $("#import-characters-btn").on("click", function () {
      $("#import-characters-modal").show();
    });

    $("#import-characters-form").on("submit", function (e) {
      e.preventDefault();
      importCharacters();
    });

    // Modal close handlers
    $(".chatstory-modal-close").on("click", function () {
      $(this).closest(".chatstory-modal").hide();
    });

    // Media uploader for character avatar
    let characterMediaUploader;

    $("#character-avatar-upload-btn").on("click", function (e) {
      e.preventDefault();

      if (characterMediaUploader) {
        characterMediaUploader.open();
        return;
      }

      characterMediaUploader = wp.media({
        title: "Select Character Avatar",
        button: {
          text: "Use this image",
        },
        multiple: false,
      });

      characterMediaUploader.on("select", function () {
        const attachment = characterMediaUploader
          .state()
          .get("selection")
          .first()
          .toJSON();

        $("#character-avatar").val(attachment.url);
        $("#character-avatar-preview img").attr("src", attachment.url);
        $("#character-avatar-preview").show();
        $("#character-avatar-remove-btn").show();
      });

      characterMediaUploader.open();
    });

    $("#character-avatar-remove-btn").on("click", function (e) {
      e.preventDefault();
      $("#character-avatar").val("");
      $("#character-avatar-preview").hide();
      $("#character-avatar-remove-btn").hide();
    });
  }

  // ==================== CHATS PAGE ====================
  if ($("#chats-table").length) {
    loadChats();

    $("#add-chat-btn").on("click", function () {
      showChatForm();
    });

    $("#cancel-chat-btn").on("click", function () {
      hideChatForm();
    });

    $("#chat-edit-form").on("submit", function (e) {
      e.preventDefault();
      saveChat();
    });

    $(document).on("click", ".edit-chat", function () {
      const id = $(this).data("id");
      editChat(id);
    });

    $(document).on("click", ".delete-chat", function () {
      const id = $(this).data("id");
      if (
        confirm(
          "Are you sure you want to delete this chat and all its messages?",
        )
      ) {
        deleteChat(id);
      }
    });

    $(document).on("click", ".preview-chat", function () {
      const id = $(this).data("id");
      previewChat(id);
    });

    $("#preview-current-chat-btn").on("click", function () {
      const chatId = $("#chat-id").val();
      if (chatId && chatId !== "0") {
        previewChat(chatId);
      }
    });

    // Message modal
    $("#add-message-btn").on("click", function () {
      showMessageModal();
    });

    $(".chatstory-modal-close").on("click", function () {
      $(this).closest(".chatstory-modal").hide();
    });

    $("#message-edit-form").on("submit", function (e) {
      e.preventDefault();
      saveMessage();
    });

    $(document).on("click", ".edit-message", function () {
      const messageId = $(this).data("id");
      const chatId = $("#chat-id").val();
      loadMessageForEdit(chatId, messageId);
    });

    $(document).on("click", ".delete-message", function () {
      const id = $(this).data("id");
      if (confirm("Are you sure you want to delete this message?")) {
        deleteMessage(id);
      }
    });

    // Reactions
    $(document).on("click", ".add-reaction", function () {
      const messageId = $(this).data("id");
      showReactionModal(messageId);
    });

    $("#reaction-edit-form").on("submit", function (e) {
      e.preventDefault();
      saveReaction();
    });

    $(document).on("click", ".reaction-delete", function (e) {
      e.stopPropagation();
      const id = $(this).data("id");
      if (confirm("Are you sure you want to delete this reaction?")) {
        deleteReaction(id);
      }
    });

    // Typing Events
    $(document).on("click", ".add-typing", function () {
      const messageId = $(this).data("id");
      showTypingModal(messageId);
    });

    $(document).on("click", ".typing-edit", function (e) {
      e.stopPropagation();
      const typingId = $(this).data("id");
      const messageId = $(this).data("message-id");
      editTypingEvent(typingId, messageId);
    });

    $("#typing-edit-form").on("submit", function (e) {
      e.preventDefault();
      saveTypingEvent();
    });

    $(document).on("click", ".typing-delete", function (e) {
      e.stopPropagation();
      const id = $(this).data("id");
      if (confirm("Are you sure you want to delete this typing event?")) {
        deleteTypingEvent(id);
      }
    });

    // Presence Events
    $("#add-presence-btn").on("click", function () {
      showPresenceModal();
    });

    $(document).on("click", ".edit-presence", function () {
      const presenceId = $(this).data("id");
      editPresenceEvent(presenceId);
    });

    $("#presence-edit-form").on("submit", function (e) {
      e.preventDefault();
      savePresenceEvent();
    });

    $(document).on("click", ".delete-presence", function () {
      const id = $(this).data("id");
      if (confirm("Are you sure you want to delete this join/leave event?")) {
        deletePresenceEvent(id);
      }
    });

    // Import JSON
    $("#import-json-btn").on("click", function () {
      $("#import-modal").show();
    });

    $("#import-json-form").on("submit", function (e) {
      e.preventDefault();
      importJSON();
    });
  }

  // ==================== CHARACTER FUNCTIONS ====================
  function loadCharacters() {
    restApiCall('characters', 'GET')
      .done(function (characters) {
        renderCharacters(characters);
      })
      .fail(function (xhr) {
        console.error('Failed to load characters:', xhr);
        alert('Error loading characters');
      });
  }

  function renderCharacters(characters) {
    const tbody = $("#characters-table tbody");
    tbody.empty();

    if (characters.length === 0) {
      tbody.append(
        '<tr class="no-items"><td colspan="4">No characters found.</td></tr>',
      );
      return;
    }

    characters.forEach(function (character) {
      const avatarHtml = character.avatar
        ? `<img src="${character.avatar}" class="character-avatar" alt="${character.name}">`
        : "-";

      tbody.append(`
                <tr>
                    <td><strong>${character.name}</strong></td>
                    <td>${character.role || "-"}</td>
                    <td>${avatarHtml}</td>
                    <td class="action-buttons">
                        <button class="button edit-character" data-id="${character.id}">Edit</button>
                        <button class="button delete-character" data-id="${character.id}">Delete</button>
                    </td>
                </tr>
            `);
    });
  }

  function showCharacterForm(character = null) {
    $("#character-form").show();
    if (character) {
      $("#form-title").text("Edit Character");
      $("#character-id").val(character.id);
      $("#character-name").val(character.name);
      $("#character-slug").val(character.slug);
      $("#character-role").val(character.role);
      $("#character-avatar").val(character.avatar);
      $("#character-traits").val(character.character_traits);

      if (character.avatar) {
        $("#character-avatar-preview img").attr("src", character.avatar);
        $("#character-avatar-preview").show();
        $("#character-avatar-remove-btn").show();
      } else {
        $("#character-avatar-preview").hide();
        $("#character-avatar-remove-btn").hide();
      }
    } else {
      $("#form-title").text("Add Character");
      $("#character-edit-form")[0].reset();
      $("#character-id").val("0");
      $("#character-avatar-preview").hide();
      $("#character-avatar-remove-btn").hide();
    }
  }

  function hideCharacterForm() {
    $("#character-form").hide();
    $("#character-edit-form")[0].reset();
  }

  function editCharacter(id) {
    restApiCall(`characters/${id}`, 'GET')
      .done(function (character) {
        showCharacterForm(character);
      })
      .fail(function (xhr) {
        console.error('Failed to load character:', xhr);
        alert('Error loading character');
      });
  }

  function saveCharacter() {
    const id = $("#character-id").val();
    const data = {
      name: $("#character-name").val(),
      slug: $("#character-slug").val(),
      role: $("#character-role").val(),
      avatar: $("#character-avatar").val(),
      character_traits: $("#character-traits").val(),
    };

    const method = (id && id !== "0") ? 'PUT' : 'POST';
    const endpoint = (id && id !== "0") ? `characters/${id}` : 'characters';

    restApiCall(endpoint, method, data)
      .done(function (response) {
        alert("Character saved successfully!");
        hideCharacterForm();
        loadCharacters();
      })
      .fail(function (xhr) {
        console.error('Failed to save character:', xhr);
        const message = xhr.responseJSON?.message || 'Unknown error';
        alert("Error: " + message);
      });
  }

  function deleteCharacter(id) {
    restApiCall(`characters/${id}`, 'DELETE')
      .done(function () {
        loadCharacters();
      })
      .fail(function (xhr) {
        console.error('Failed to delete character:', xhr);
        const message = xhr.responseJSON?.message || 'Unknown error';
        alert("Error: " + message);
      });
  }

  function importCharacters() {
    const jsonData = $("#import-characters-data").val();
    let characters;

    try {
      characters = JSON.parse(jsonData);
    } catch (e) {
      alert("Invalid JSON format");
      return;
    }

    restApiCall('characters/import', 'POST', { characters: characters })
      .done(function (response) {
        alert(`Characters imported successfully! Imported: ${response.imported}, Skipped: ${response.skipped}`);
        $("#import-characters-modal").hide();
        $("#import-characters-data").val("");
        loadCharacters();
      })
      .fail(function (xhr) {
        console.error('Failed to import characters:', xhr);
        const message = xhr.responseJSON?.message || 'Unknown error';
        alert("Error: " + message);
      });
  }

  // ==================== CHAT FUNCTIONS ====================
  function loadChats() {
    restApiCall('chats', 'GET')
      .done(function (chats) {
        renderChats(chats);
      })
      .fail(function (xhr) {
        console.error('Failed to load chats:', xhr);
        alert('Error loading chats');
      });
  }

  function renderChats(chats) {
    const tbody = $("#chats-table tbody");
    tbody.empty();

    if (chats.length === 0) {
      tbody.append(
        '<tr class="no-items"><td colspan="4">No chats found.</td></tr>',
      );
      return;
    }

    chats.forEach(function (chat) {
      tbody.append(`
                <tr>
                    <td><strong>${chat.title}</strong></td>
                    <td>${chat.description || "-"}</td>
                    <td>-</td>
                    <td class="action-buttons">
                        <button class="button preview-chat" data-id="${chat.id}">Preview</button>
                        <button class="button edit-chat" data-id="${chat.id}">Edit</button>
                        <button class="button delete-chat" data-id="${chat.id}">Delete</button>
                    </td>
                </tr>
            `);
    });
  }

  function showChatForm(chat = null) {
    $(".chatstory-list").hide();
    $("#chat-form").show();
    if (chat) {
      $("#chat-form-title").text("Edit Chat");
      $("#chat-id").val(chat.id);
      $("#chat-title").val(chat.title);
      $("#chat-description").val(chat.description);
      $("#messages-section").show();
      loadMessages(chat.id);
    } else {
      $("#chat-form-title").text("Add Chat");
      $("#chat-edit-form")[0].reset();
      $("#chat-id").val("0");
      $("#messages-section").hide();
    }
  }

  function hideChatForm() {
    $("#chat-form").hide();
    $("#chat-edit-form")[0].reset();
    $("#messages-section").hide();
    $(".chatstory-list").show();
  }

  function editChat(id) {
    restApiCall(`chats/${id}`, 'GET')
      .done(function (response) {
        showChatForm(response.chat);
      })
      .fail(function (xhr) {
        console.error('Failed to load chat:', xhr);
        alert('Error loading chat');
      });
  }

  function saveChat() {
    const id = $("#chat-id").val();
    const data = {
      title: $("#chat-title").val(),
      description: $("#chat-description").val(),
    };

    const method = (id && id !== "0") ? 'PUT' : 'POST';
    const endpoint = (id && id !== "0") ? `chats/${id}` : 'chats';

    restApiCall(endpoint, method, data)
      .done(function (response) {
        alert("Chat saved successfully!");
        const chatId = response.id || id;
        $("#chat-id").val(chatId);
        $("#messages-section").show();
        loadMessages(chatId);
        loadChats();
      })
      .fail(function (xhr) {
        console.error('Failed to save chat:', xhr);
        const message = xhr.responseJSON?.message || 'Unknown error';
        alert("Error: " + message);
      });
  }

  function deleteChat(id) {
    restApiCall(`chats/${id}`, 'DELETE')
      .done(function () {
        loadChats();
        hideChatForm();
      })
      .fail(function (xhr) {
        console.error('Failed to delete chat:', xhr);
        const message = xhr.responseJSON?.message || 'Unknown error';
        alert("Error: " + message);
      });
  }

  // ==================== MESSAGE FUNCTIONS ====================
  function loadMessages(chatId) {
    $("#message-chat-id").val(chatId);

    restApiCall(`chats/${chatId}`, 'GET')
      .done(function (response) {
        renderMessagesAndEvents(
          response.messages || [],
          response.presence_events || []
        );

        const shortcode = `[chatstory id="${chatId}"]`;
        if ($("#shortcode-display").length === 0) {
          $("#messages-section").prepend(`
            <div class="shortcode-display" id="shortcode-display">
              <strong>Shortcode:</strong> <code>${shortcode}</code>
            </div>
          `);
        }
      })
      .fail(function (xhr) {
        console.error('Failed to load messages:', xhr);
        alert('Error loading messages');
      });
  }

  function renderMessagesAndEvents(messages, presenceEvents) {
    const container = $("#messages-list");
    container.empty();

    const allItems = [];

    messages.forEach(function (msg) {
      allItems.push({
        type: "message",
        start_time: parseFloat(msg.start_time),
        data: msg,
      });
    });

    presenceEvents.forEach(function (presence) {
      allItems.push({
        type: "presence",
        start_time: parseFloat(presence.start_time),
        data: presence,
      });
    });

    allItems.sort((a, b) => a.start_time - b.start_time);

    if (allItems.length === 0) {
      container.append("<p>No messages or events yet. Add your first message!</p>");
      return;
    }

    allItems.forEach(function (item) {
      if (item.type === "message") {
        renderMessage(container, item.data);
      } else if (item.type === "presence") {
        renderPresenceEvent(container, item.data);
      }
    });
  }

  function renderMessage(container, message) {
    let reactionsHtml = "";
    if (message.reactions && message.reactions.length > 0) {
      reactionsHtml = '<div class="message-reactions-list">';
      message.reactions.forEach(function (reaction) {
        reactionsHtml += `
          <span class="reaction-badge" title="${reaction.name} reacted @ ${reaction.start_time}s">
            ${reaction.reaction}
            <button class="reaction-delete" data-id="${reaction.id}">&times;</button>
          </span>
        `;
      });
      reactionsHtml += "</div>";
    }

    let typingHtml = "";
    if (message.typing_event) {
      const typing = message.typing_event;
      const calculatedStart = Math.max(0, message.start_time - typing.duration);
      typingHtml = `
        <div class="message-typing-indicator">
          <span class="typing-badge" title="Typing starts ${typing.duration}s before message (at ${calculatedStart}s)">
            ⌨️ Typing: ${typing.duration}s before message
            <button class="typing-edit" data-id="${typing.id}" data-message-id="${message.id}">✏️</button>
            <button class="typing-delete" data-id="${typing.id}">&times;</button>
          </span>
        </div>
      `;
    }

    container.append(`
      <div class="message-item" data-message-id="${message.id}">
        <div class="message-item-header">
          <div>
            <span class="message-character">${message.name || "Unknown"}</span>
            ${message.role ? `<span class="message-role"> (${message.role})</span>` : ""}
          </div>
          <div>
            ${message.timestamp ? `<span class="message-timestamp">${message.timestamp}</span>` : ""}
            <span class="message-order-badge">@ ${message.start_time}s</span>
          </div>
        </div>
        <div class="message-text">${message.message}</div>
        ${typingHtml}
        ${reactionsHtml}
        <div class="message-actions">
          <button class="button button-small edit-message" data-id="${message.id}">Edit</button>
          <button class="button button-small delete-message" data-id="${message.id}">Delete</button>
          <button class="button button-small add-reaction" data-id="${message.id}">Add Reaction</button>
          ${!message.typing_event ? `<button class="button button-small add-typing" data-id="${message.id}">Add Typing</button>` : ""}
        </div>
      </div>
    `);
  }

  function renderPresenceEvent(container, presence) {
    const actionText =
      presence.action === "join" ? "joined the chat" : "left the chat";
    const actionIcon = presence.action === "join" ? "➕" : "➖";
    const actionClass =
      presence.action === "join" ? "presence-join" : "presence-leave";

    container.append(`
      <div class="presence-item ${actionClass}" data-presence-id="${presence.id}">
        <div class="presence-header">
          <span class="presence-icon">${actionIcon}</span>
          <span class="presence-character">${presence.name || "Unknown"}</span>
          <span class="presence-action">${actionText}</span>
          <span class="presence-time-badge">@ ${presence.start_time}s</span>
        </div>
        <div class="presence-actions">
          <button class="button button-small edit-presence" data-id="${presence.id}">Edit</button>
          <button class="button button-small delete-presence" data-id="${presence.id}">Delete</button>
        </div>
      </div>
    `);
  }

  function showMessageModal() {
    loadCharactersForSelect();
    $("#message-modal-title").text("Add Message");
    $("#message-edit-form")[0].reset();
    $("#message-id").val("0");
    const lastMessage = $("#messages-list .message-item").last();
    const nextStartTime = lastMessage.length
      ? parseFloat(
          lastMessage
            .find(".message-order-badge")
            .text()
            .replace(/[^0-9.]/g, ""),
        ) + 2
      : 0;
    $("#message-start-time").val(nextStartTime);
    $("#message-modal").show();
  }

  function loadMessageForEdit(chatId, messageId) {
    $("#message-chat-id").val(chatId);

    restApiCall(`chats/${chatId}`, 'GET')
      .done(function (response) {
        if (response.messages) {
          const message = response.messages.find((m) => m.id == messageId);
          if (message) {
            editMessage(message);
          } else {
            alert("Message not found");
          }
        }
      })
      .fail(function (xhr) {
        console.error('Failed to load message:', xhr);
        alert('Error loading message');
      });
  }

  function editMessage(messageData) {
    loadCharactersForSelect();
    $("#message-modal-title").text("Edit Message");
    $("#message-id").val(messageData.id);
    $("#message-character").val(messageData.character_id);
    $("#message-text").val(messageData.message);
    $("#message-timestamp").val(messageData.timestamp);
    $("#message-start-time").val(messageData.start_time || 0);
    $("#message-modal").show();
  }

  function loadCharactersForSelect() {
    restApiCall('characters', 'GET')
      .done(function (characters) {
        const select = $("#message-character");
        const currentVal = select.val();
        select.find("option:not(:first)").remove();

        characters.forEach(function (character) {
          select.append(
            `<option value="${character.id}">${character.name} ${character.role ? "(" + character.role + ")" : ""}</option>`,
          );
        });

        if (currentVal) {
          select.val(currentVal);
        }
      });
  }

  function saveMessage() {
    const chatId = $("#message-chat-id").val();
    const id = $("#message-id").val();
    const data = {
      character_id: parseInt($("#message-character").val()),
      message: $("#message-text").val(),
      timestamp: $("#message-timestamp").val(),
      start_time: parseFloat($("#message-start-time").val()),
    };

    const method = (id && id !== "0") ? 'PUT' : 'POST';
    const endpoint = (id && id !== "0") ? `messages/${id}` : `chats/${chatId}/messages`;

    if (method === 'POST') {
      data.chat_id = parseInt(chatId);
    }

    restApiCall(endpoint, method, data)
      .done(function () {
        alert("Message saved successfully!");
        $("#message-modal").hide();
        loadMessages(chatId);
      })
      .fail(function (xhr) {
        console.error('Failed to save message:', xhr);
        const message = xhr.responseJSON?.message || 'Unknown error';
        alert("Error: " + message);
      });
  }

  function deleteMessage(id) {
    const chatId = $("#message-chat-id").val();

    restApiCall(`messages/${id}`, 'DELETE')
      .done(function () {
        loadMessages(chatId);
      })
      .fail(function (xhr) {
        console.error('Failed to delete message:', xhr);
        const message = xhr.responseJSON?.message || 'Unknown error';
        alert("Error: " + message);
      });
  }

  // ==================== REACTION FUNCTIONS ====================
  function showReactionModal(messageId) {
    const chatId = $("#chat-id").val();
    const messageItem = $(`.message-item[data-message-id="${messageId}"]`);
    const messageStartTime = parseFloat(
      messageItem
        .find(".message-order-badge")
        .text()
        .replace(/[^0-9.]/g, ""),
    );

    loadCharactersForReactionSelect();
    $("#reaction-modal-title").text("Add Reaction");
    $("#reaction-edit-form")[0].reset();
    $("#reaction-id").val("0");
    $("#reaction-chat-id").val(chatId);
    $("#reaction-target-event-id").val(messageId);
    $("#reaction-start-time").val(messageStartTime + 1);
    $("#reaction-modal").show();
  }

  function loadCharactersForReactionSelect() {
    restApiCall('characters', 'GET')
      .done(function (characters) {
        const select = $("#reaction-character");
        const currentVal = select.val();
        select.find("option:not(:first)").remove();

        characters.forEach(function (character) {
          select.append(
            `<option value="${character.id}">${character.name} ${character.role ? "(" + character.role + ")" : ""}</option>`,
          );
        });

        if (currentVal) {
          select.val(currentVal);
        }
      });
  }

  function saveReaction() {
    const chatId = $("#reaction-chat-id").val();
    const messageId = $("#reaction-target-event-id").val();
    const data = {
      chat_id: parseInt(chatId),
      character_id: parseInt($("#reaction-character").val()),
      reaction_type: $("#reaction-type").val(),
      start_time: parseFloat($("#reaction-start-time").val()),
    };

    restApiCall(`messages/${messageId}/reactions`, 'POST', data)
      .done(function () {
        alert("Reaction saved successfully!");
        $("#reaction-modal").hide();
        loadMessages(chatId);
      })
      .fail(function (xhr) {
        console.error('Failed to save reaction:', xhr);
        const message = xhr.responseJSON?.message || 'Unknown error';
        alert("Error: " + message);
      });
  }

  function deleteReaction(id) {
    const chatId = $("#reaction-chat-id").val() || $("#chat-id").val();

    restApiCall(`reactions/${id}`, 'DELETE')
      .done(function () {
        loadMessages(chatId);
      })
      .fail(function (xhr) {
        console.error('Failed to delete reaction:', xhr);
        const message = xhr.responseJSON?.message || 'Unknown error';
        alert("Error: " + message);
      });
  }

  // ==================== TYPING EVENT FUNCTIONS ====================
  function showTypingModal(messageId) {
    const chatId = $("#chat-id").val();

    restApiCall(`chats/${chatId}`, 'GET')
      .done(function (response) {
        if (response.messages) {
          const message = response.messages.find((m) => m.id == messageId);
          if (message) {
            $("#typing-modal-title").text("Add Typing Event");
            $("#typing-edit-form")[0].reset();
            $("#typing-id").val("0");
            $("#typing-chat-id").val(chatId);
            $("#typing-target-event-id").val(messageId);
            $("#typing-character-id").val(message.character_id);
            $("#typing-duration").val(3);
            $("#typing-modal").show();
          }
        }
      });
  }

  function editTypingEvent(typingId, messageId) {
    const chatId = $("#chat-id").val();

    restApiCall(`chats/${chatId}`, 'GET')
      .done(function (response) {
        if (response.messages) {
          const message = response.messages.find((m) => m.id == messageId);
          if (message && message.typing_event) {
            const typing = message.typing_event;
            $("#typing-modal-title").text("Edit Typing Event");
            $("#typing-id").val(typing.id);
            $("#typing-chat-id").val(chatId);
            $("#typing-target-event-id").val(messageId);
            $("#typing-character-id").val(typing.character_id);
            $("#typing-duration").val(typing.duration);
            $("#typing-modal").show();
          }
        }
      });
  }

  function saveTypingEvent() {
    const chatId = $("#typing-chat-id").val();
    const messageId = $("#typing-target-event-id").val();
    const id = $("#typing-id").val();
    const duration = parseFloat($("#typing-duration").val());

    restApiCall(`chats/${chatId}`, 'GET')
      .done(function (response) {
        if (response.messages) {
          const message = response.messages.find((m) => m.id == messageId);
          if (message) {
            const startTime = Math.max(0, message.start_time - duration);
            const data = {
              chat_id: parseInt(chatId),
              character_id: parseInt($("#typing-character-id").val()),
              duration: duration,
              start_time: startTime,
            };

            const method = (id && id !== "0") ? 'PUT' : 'POST';
            const endpoint = (id && id !== "0") ? `typing/${id}` : `messages/${messageId}/typing`;

            restApiCall(endpoint, method, data)
              .done(function () {
                alert("Typing event saved successfully!");
                $("#typing-modal").hide();
                loadMessages(chatId);
              })
              .fail(function (xhr) {
                console.error('Failed to save typing event:', xhr);
                const message = xhr.responseJSON?.message || 'Unknown error';
                alert("Error: " + message);
              });
          }
        }
      });
  }

  function deleteTypingEvent(id) {
    const chatId = $("#typing-chat-id").val() || $("#chat-id").val();

    restApiCall(`typing/${id}`, 'DELETE')
      .done(function () {
        loadMessages(chatId);
      })
      .fail(function (xhr) {
        console.error('Failed to delete typing event:', xhr);
        const message = xhr.responseJSON?.message || 'Unknown error';
        alert("Error: " + message);
      });
  }

  // ==================== PRESENCE EVENT FUNCTIONS ====================
  function showPresenceModal() {
    const chatId = $("#chat-id").val();
    loadCharactersForPresenceSelect();
    $("#presence-modal-title").text("Add Join/Leave Event");
    $("#presence-edit-form")[0].reset();
    $("#presence-id").val("0");
    $("#presence-chat-id").val(chatId);
    $("#presence-start-time").val(0);
    $("#presence-modal").show();
  }

  function editPresenceEvent(presenceId) {
    const chatId = $("#chat-id").val();

    restApiCall(`chats/${chatId}`, 'GET')
      .done(function (response) {
        if (response.presence_events) {
          const presence = response.presence_events.find((p) => p.id == presenceId);
          if (presence) {
            loadCharactersForPresenceSelect();
            setTimeout(function() {
              $("#presence-modal-title").text("Edit Join/Leave Event");
              $("#presence-id").val(presence.id);
              $("#presence-chat-id").val(chatId);
              $("#presence-character").val(presence.character_id);
              $("#presence-action").val(presence.action);
              $("#presence-start-time").val(presence.start_time);
              $("#presence-modal").show();
            }, 100);
          }
        }
      });
  }

  function loadCharactersForPresenceSelect() {
    restApiCall('characters', 'GET')
      .done(function (characters) {
        const select = $("#presence-character");
        select.find("option:not(:first)").remove();
        characters.forEach(function (character) {
          select.append(
            `<option value="${character.id}">${character.name}</option>`,
          );
        });
      });
  }

  function savePresenceEvent() {
    const chatId = $("#presence-chat-id").val();
    const id = $("#presence-id").val();
    const data = {
      character_id: parseInt($("#presence-character").val()),
      presence_action: $("#presence-action").val(),
      start_time: parseFloat($("#presence-start-time").val()),
    };

    const method = (id && id !== "0") ? 'PUT' : 'POST';
    const endpoint = (id && id !== "0") ? `presence/${id}` : `chats/${chatId}/presence`;

    if (method === 'POST') {
      data.chat_id = parseInt(chatId);
    }

    restApiCall(endpoint, method, data)
      .done(function () {
        alert("Join/Leave event saved successfully!");
        $("#presence-modal").hide();
        loadMessages(chatId);
      })
      .fail(function (xhr) {
        console.error('Failed to save presence event:', xhr);
        const message = xhr.responseJSON?.message || 'Unknown error';
        alert("Error: " + message);
      });
  }

  function deletePresenceEvent(id) {
    const chatId = $("#presence-chat-id").val() || $("#chat-id").val();

    restApiCall(`presence/${id}`, 'DELETE')
      .done(function () {
        loadMessages(chatId);
      })
      .fail(function (xhr) {
        console.error('Failed to delete presence event:', xhr);
        const message = xhr.responseJSON?.message || 'Unknown error';
        alert("Error: " + message);
      });
  }

  // ==================== IMPORT FUNCTIONS ====================
  function importJSON() {
    const jsonData = $("#import-json-data").val();
    let data;

    try {
      data = JSON.parse(jsonData);
    } catch (e) {
      alert("Invalid JSON format");
      return;
    }

    restApiCall('chats/import', 'POST', data)
      .done(function () {
        alert("Import successful!");
        $("#import-modal").hide();
        loadChats();
      })
      .fail(function (xhr) {
        console.error('Failed to import:', xhr);
        const message = xhr.responseJSON?.message || 'Unknown error';
        alert("Error: " + message);
      });
  }

  // ==================== PREVIEW FUNCTIONS ====================
  function previewChat(chatId) {
    restApiCall(`chats/${chatId}/preview-url`, 'GET')
      .done(function (response) {
        if (response.url) {
          window.open(response.url, "_blank");
        }
      })
      .fail(function (xhr) {
        console.error('Failed to get preview URL:', xhr);
        alert('Error generating preview URL');
      });
  }

  function escapeHtml(text) {
    const map = {
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#039;",
    };
    return String(text).replace(/[&<>"']/g, function (m) {
      return map[m];
    });
  }
});
