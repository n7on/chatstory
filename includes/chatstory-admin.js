jQuery(document).ready(function ($) {
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

      // If the uploader object has already been created, reopen the dialog
      if (characterMediaUploader) {
        characterMediaUploader.open();
        return;
      }

      // Create the media uploader
      characterMediaUploader = wp.media({
        title: "Select Character Avatar",
        button: {
          text: "Use this image",
        },
        multiple: false,
      });

      // When an image is selected, run a callback
      characterMediaUploader.on("select", function () {
        const attachment = characterMediaUploader
          .state()
          .get("selection")
          .first()
          .toJSON();

        // Set the avatar URL
        $("#character-avatar").val(attachment.url);

        // Show preview
        $("#character-avatar-preview img").attr("src", attachment.url);
        $("#character-avatar-preview").show();
        $("#character-avatar-remove-btn").show();
      });

      // Open the uploader dialog
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
    $.post(
      ChatStoryAjax.ajax_url,
      {
        action: "chatstory_get_characters",
        nonce: ChatStoryAjax.nonce,
      },
      function (response) {
        if (response.success) {
          renderCharacters(response.data);
        }
      },
    );
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

      // Show avatar preview if exists
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
    $.post(
      ChatStoryAjax.ajax_url,
      {
        action: "chatstory_get_characters",
        nonce: ChatStoryAjax.nonce,
      },
      function (response) {
        if (response.success) {
          const character = response.data.find((p) => p.id == id);
          if (character) {
            showCharacterForm(character);
          }
        }
      },
    );
  }

  function saveCharacter() {
    const data = {
      action: "chatstory_save_character",
      nonce: ChatStoryAjax.nonce,
      id: $("#character-id").val(),
      name: $("#character-name").val(),
      slug: $("#character-slug").val(),
      role: $("#character-role").val(),
      avatar: $("#character-avatar").val(),
      character_traits: $("#character-traits").val(),
    };

    $.post(ChatStoryAjax.ajax_url, data, function (response) {
      if (response.success) {
        alert("Character saved successfully!");
        hideCharacterForm();
        loadCharacters();
      } else {
        alert("Error: " + (response.data.message || "Unknown error"));
      }
    });
  }

  function deleteCharacter(id) {
    $.post(
      ChatStoryAjax.ajax_url,
      {
        action: "chatstory_delete_character",
        nonce: ChatStoryAjax.nonce,
        id: id,
      },
      function (response) {
        if (response.success) {
          loadCharacters();
        } else {
          alert("Error: " + (response.data.message || "Unknown error"));
        }
      },
    );
  }

  function importCharacters() {
    const jsonData = $("#import-characters-data").val();

    $.post(
      ChatStoryAjax.ajax_url,
      {
        action: "chatstory_import_characters",
        nonce: ChatStoryAjax.nonce,
        json: jsonData,
      },
      function (response) {
        if (response.success) {
          alert("Characters imported successfully!");
          $("#import-characters-modal").hide();
          $("#import-characters-data").val("");
          loadCharacters();
        } else {
          alert("Error: " + (response.data.message || "Unknown error"));
        }
      },
    );
  }

  // ==================== CHAT FUNCTIONS ====================
  function loadChats() {
    $.post(
      ChatStoryAjax.ajax_url,
      {
        action: "chatstory_get_chats",
        nonce: ChatStoryAjax.nonce,
      },
      function (response) {
        if (response.success) {
          renderChats(response.data);
        }
      },
    );
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
    $.post(
      ChatStoryAjax.ajax_url,
      {
        action: "chatstory_get_chat",
        nonce: ChatStoryAjax.nonce,
        id: id,
      },
      function (response) {
        if (response.success) {
          showChatForm(response.data.chat);
        }
      },
    );
  }

  function saveChat() {
    const data = {
      action: "chatstory_save_chat",
      nonce: ChatStoryAjax.nonce,
      id: $("#chat-id").val(),
      title: $("#chat-title").val(),
      description: $("#chat-description").val(),
    };

    $.post(ChatStoryAjax.ajax_url, data, function (response) {
      if (response.success) {
        alert("Chat saved successfully!");
        $("#chat-id").val(response.data.id);
        $("#messages-section").show();
        loadMessages(response.data.id);
        loadChats();
      } else {
        alert("Error: " + (response.data.message || "Unknown error"));
      }
    });
  }

  function deleteChat(id) {
    $.post(
      ChatStoryAjax.ajax_url,
      {
        action: "chatstory_delete_chat",
        nonce: ChatStoryAjax.nonce,
        id: id,
      },
      function (response) {
        if (response.success) {
          loadChats();
          hideChatForm();
        } else {
          alert("Error: " + (response.data.message || "Unknown error"));
        }
      },
    );
  }

  // ==================== MESSAGE FUNCTIONS ====================
  function loadMessages(chatId) {
    $("#message-chat-id").val(chatId);

    $.post(
      ChatStoryAjax.ajax_url,
      {
        action: "chatstory_get_chat",
        nonce: ChatStoryAjax.nonce,
        id: chatId,
      },
      function (response) {
        console.log("loadMessages response:", response);
        if (response.success && response.data && response.data.messages) {
          renderMessagesAndEvents(
            response.data.messages,
            response.data.presence_events || [],
          );

          // Show shortcode
          const shortcode = `[chatstory id="${chatId}"]`;
          if ($("#shortcode-display").length === 0) {
            $("#messages-section").prepend(`
                        <div class="shortcode-display" id="shortcode-display">
                            <strong>Shortcode:</strong> <code>${shortcode}</code>
                        </div>
                    `);
          }
        } else {
          console.error("Failed to load messages:", response);
          alert(
            "Error loading messages: " +
              (response.data?.message || "Unknown error"),
          );
        }
      },
    ).fail(function (xhr, status, error) {
      console.error("AJAX failed:", xhr, status, error);
      console.error("Response text:", xhr.responseText);
      alert("Failed to load messages. Check console for details.");
    });
  }

  function renderMessagesAndEvents(messages, presenceEvents) {
    const container = $("#messages-list");
    container.empty();

    // Combine messages and presence events with a type indicator
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

    // Sort by start_time
    allItems.sort((a, b) => a.start_time - b.start_time);

    if (allItems.length === 0) {
      container.append("<p>No messages or events yet. Add your first message!</p>");
      return;
    }

    // Render each item in order
    allItems.forEach(function (item) {
      if (item.type === "message") {
        renderMessage(container, item.data);
      } else if (item.type === "presence") {
        renderPresenceEvent(container, item.data);
      }
    });
  }

  function renderMessage(container, message) {
      // Build reactions HTML
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

      // Build typing event HTML
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
    // Set start_time to be after the last message
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
    // Make sure chat ID is set for saving
    $("#message-chat-id").val(chatId);

    $.post(
      ChatStoryAjax.ajax_url,
      {
        action: "chatstory_get_chat",
        nonce: ChatStoryAjax.nonce,
        id: chatId,
      },
      function (response) {
        if (response.success && response.data.messages) {
          const message = response.data.messages.find((m) => m.id == messageId);
          if (message) {
            editMessage(message);
          } else {
            alert("Message not found");
          }
        } else {
          alert(
            "Error loading message: " +
              (response.data?.message || "Unknown error"),
          );
        }
      },
    ).fail(function () {
      alert("Failed to load message data");
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
    $.post(
      ChatStoryAjax.ajax_url,
      {
        action: "chatstory_get_characters",
        nonce: ChatStoryAjax.nonce,
      },
      function (response) {
        if (response.success) {
          const select = $("#message-character");
          const currentVal = select.val();
          select.find("option:not(:first)").remove();

          response.data.forEach(function (character) {
            select.append(
              `<option value="${character.id}">${character.name} ${character.role ? "(" + character.role + ")" : ""}</option>`,
            );
          });

          if (currentVal) {
            select.val(currentVal);
          }
        }
      },
    );
  }

  function saveMessage() {
    const chatId = $("#message-chat-id").val();

    console.log("Saving message, chat ID:", chatId);

    const data = {
      action: "chatstory_save_message",
      nonce: ChatStoryAjax.nonce,
      id: $("#message-id").val(),
      chat_id: chatId,
      character_id: $("#message-character").val(),
      message: $("#message-text").val(),
      timestamp: $("#message-timestamp").val(),
      start_time: $("#message-start-time").val(),
    };

    console.log("Save data:", data);

    $.post(ChatStoryAjax.ajax_url, data, function (response) {
      console.log("Save response:", response);
      if (response.success) {
        alert("Message saved successfully!");
        $("#message-modal").hide();
        loadMessages(chatId);
      } else {
        alert("Error: " + (response.data?.message || "Unknown error"));
      }
    }).fail(function (xhr, status, error) {
      console.error("Save failed:", xhr, status, error);
      console.error("Response text:", xhr.responseText);
      alert("Save failed: " + error + "\n\nCheck console for details.");
    });
  }

  function deleteMessage(id) {
    const chatId = $("#message-chat-id").val();

    $.post(
      ChatStoryAjax.ajax_url,
      {
        action: "chatstory_delete_message",
        nonce: ChatStoryAjax.nonce,
        id: id,
      },
      function (response) {
        if (response.success) {
          loadMessages(chatId);
        } else {
          alert("Error: " + (response.data.message || "Unknown error"));
        }
      },
    );
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
    $("#reaction-start-time").val(messageStartTime + 1); // Default to 1 second after message
    $("#reaction-modal").show();
  }

  function loadCharactersForReactionSelect() {
    $.post(
      ChatStoryAjax.ajax_url,
      {
        action: "chatstory_get_characters",
        nonce: ChatStoryAjax.nonce,
      },
      function (response) {
        if (response.success) {
          const select = $("#reaction-character");
          const currentVal = select.val();
          select.find("option:not(:first)").remove();

          response.data.forEach(function (character) {
            select.append(
              `<option value="${character.id}">${character.name} ${character.role ? "(" + character.role + ")" : ""}</option>`,
            );
          });

          if (currentVal) {
            select.val(currentVal);
          }
        }
      },
    );
  }

  function saveReaction() {
    const chatId = $("#reaction-chat-id").val();

    const data = {
      action: "chatstory_save_reaction",
      nonce: ChatStoryAjax.nonce,
      id: $("#reaction-id").val(),
      chat_id: chatId,
      character_id: $("#reaction-character").val(),
      target_event_id: $("#reaction-target-event-id").val(),
      reaction_type: $("#reaction-type").val(),
      start_time: $("#reaction-start-time").val(),
    };

    $.post(ChatStoryAjax.ajax_url, data, function (response) {
      if (response.success) {
        alert("Reaction saved successfully!");
        $("#reaction-modal").hide();
        loadMessages(chatId);
      } else {
        alert("Error: " + (response.data?.message || "Unknown error"));
      }
    }).fail(function (xhr, status, error) {
      console.error("Save failed:", xhr, status, error);
      alert("Save failed: " + error);
    });
  }

  function deleteReaction(id) {
    const chatId = $("#reaction-chat-id").val() || $("#chat-id").val();

    $.post(
      ChatStoryAjax.ajax_url,
      {
        action: "chatstory_delete_reaction",
        nonce: ChatStoryAjax.nonce,
        id: id,
      },
      function (response) {
        if (response.success) {
          loadMessages(chatId);
        } else {
          alert("Error: " + (response.data.message || "Unknown error"));
        }
      },
    );
  }

  // ==================== TYPING EVENT FUNCTIONS ====================
  function showTypingModal(messageId) {
    const chatId = $("#chat-id").val();

    // Get character ID from message
    $.post(
      ChatStoryAjax.ajax_url,
      {
        action: "chatstory_get_chat",
        nonce: ChatStoryAjax.nonce,
        id: chatId,
      },
      function (response) {
        if (response.success && response.data.messages) {
          const message = response.data.messages.find(
            (m) => m.id == messageId,
          );
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
      },
    );
  }

  function editTypingEvent(typingId, messageId) {
    const chatId = $("#chat-id").val();

    $.post(
      ChatStoryAjax.ajax_url,
      {
        action: "chatstory_get_chat",
        nonce: ChatStoryAjax.nonce,
        id: chatId,
      },
      function (response) {
        if (response.success && response.data.messages) {
          const message = response.data.messages.find(
            (m) => m.id == messageId,
          );
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
      },
    );
  }

  function saveTypingEvent() {
    const chatId = $("#typing-chat-id").val();
    const targetEventId = $("#typing-target-event-id").val();
    const duration = parseFloat($("#typing-duration").val());

    // Get the message to calculate start_time
    $.post(
      ChatStoryAjax.ajax_url,
      {
        action: "chatstory_get_chat",
        nonce: ChatStoryAjax.nonce,
        id: chatId,
      },
      function (response) {
        if (response.success && response.data.messages) {
          const message = response.data.messages.find(
            (m) => m.id == targetEventId,
          );
          if (message) {
            // Calculate start_time: message.start_time - duration
            const startTime = Math.max(0, message.start_time - duration);

            const data = {
              action: "chatstory_save_typing",
              nonce: ChatStoryAjax.nonce,
              id: $("#typing-id").val(),
              chat_id: chatId,
              character_id: $("#typing-character-id").val(),
              target_event_id: targetEventId,
              duration: duration,
              start_time: startTime,
            };

            $.post(ChatStoryAjax.ajax_url, data, function (response) {
              if (response.success) {
                alert("Typing event saved successfully!");
                $("#typing-modal").hide();
                loadMessages(chatId);
              } else {
                alert("Error: " + (response.data?.message || "Unknown error"));
              }
            }).fail(function (xhr, status, error) {
              console.error("Save failed:", xhr, status, error);
              alert("Save failed: " + error);
            });
          }
        } else {
          alert("Could not find message to calculate timing");
        }
      },
    );
  }

  function deleteTypingEvent(id) {
    const chatId = $("#typing-chat-id").val() || $("#chat-id").val();

    $.post(
      ChatStoryAjax.ajax_url,
      {
        action: "chatstory_delete_typing",
        nonce: ChatStoryAjax.nonce,
        id: id,
      },
      function (response) {
        if (response.success) {
          loadMessages(chatId);
        } else {
          alert("Error: " + (response.data.message || "Unknown error"));
        }
      },
    );
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

    $.post(
      ChatStoryAjax.ajax_url,
      {
        action: "chatstory_get_chat",
        nonce: ChatStoryAjax.nonce,
        id: chatId,
      },
      function (response) {
        if (response.success && response.data.presence_events) {
          const presence = response.data.presence_events.find(
            (p) => p.id == presenceId,
          );
          if (presence) {
            // Load characters first, then populate form
            $.post(
              ChatStoryAjax.ajax_url,
              {
                action: "chatstory_get_characters",
                nonce: ChatStoryAjax.nonce,
              },
              function (charResponse) {
                if (charResponse.success) {
                  const select = $("#presence-character");
                  select.find("option:not(:first)").remove();
                  charResponse.data.forEach(function (character) {
                    select.append(
                      `<option value="${character.id}">${character.name}</option>`,
                    );
                  });

                  // Now set the values after options are loaded
                  $("#presence-modal-title").text("Edit Join/Leave Event");
                  $("#presence-id").val(presence.id);
                  $("#presence-chat-id").val(chatId);
                  $("#presence-character").val(presence.character_id);
                  $("#presence-action").val(presence.action);
                  $("#presence-start-time").val(presence.start_time);
                  $("#presence-modal").show();
                }
              },
            );
          }
        }
      },
    );
  }

  function loadCharactersForPresenceSelect() {
    $.post(
      ChatStoryAjax.ajax_url,
      {
        action: "chatstory_get_characters",
        nonce: ChatStoryAjax.nonce,
      },
      function (response) {
        if (response.success) {
          const select = $("#presence-character");
          select.find("option:not(:first)").remove();
          response.data.forEach(function (character) {
            select.append(
              `<option value="${character.id}">${character.name}</option>`,
            );
          });
        }
      },
    );
  }

  function savePresenceEvent() {
    const chatId = $("#presence-chat-id").val();

    const data = {
      action: "chatstory_save_presence",
      nonce: ChatStoryAjax.nonce,
      id: $("#presence-id").val(),
      chat_id: chatId,
      character_id: $("#presence-character").val(),
      presence_action: $("#presence-action").val(),
      start_time: $("#presence-start-time").val(),
    };

    $.post(ChatStoryAjax.ajax_url, data, function (response) {
      if (response.success) {
        alert("Join/Leave event saved successfully!");
        $("#presence-modal").hide();
        loadMessages(chatId);
      } else {
        alert("Error: " + (response.data?.message || "Unknown error"));
      }
    }).fail(function (xhr, status, error) {
      console.error("Save failed:", xhr, status, error);
      alert("Save failed: " + error);
    });
  }

  function deletePresenceEvent(id) {
    const chatId = $("#presence-chat-id").val() || $("#chat-id").val();

    $.post(
      ChatStoryAjax.ajax_url,
      {
        action: "chatstory_delete_presence",
        nonce: ChatStoryAjax.nonce,
        id: id,
      },
      function (response) {
        if (response.success) {
          loadMessages(chatId);
        } else {
          alert("Error: " + (response.data.message || "Unknown error"));
        }
      },
    );
  }

  // ==================== IMPORT FUNCTIONS ====================
  function importJSON() {
    const jsonData = $("#import-json-data").val();

    $.post(
      ChatStoryAjax.ajax_url,
      {
        action: "chatstory_import_json",
        nonce: ChatStoryAjax.nonce,
        json: jsonData,
      },
      function (response) {
        if (response.success) {
          alert("Import successful!");
          $("#import-modal").hide();
          loadChats();
          loadCharacters();
        } else {
          alert("Error: " + (response.data.message || "Unknown error"));
        }
      },
    );
  }

  // ==================== PREVIEW FUNCTIONS ====================
  function previewChat(chatId) {
    $.post(
      ChatStoryAjax.ajax_url,
      {
        action: "chatstory_get_preview_url",
        nonce: ChatStoryAjax.nonce,
        chat_id: chatId,
      },
      function (response) {
        if (response.success && response.data.url) {
          // Open preview in a new tab
          window.open(response.data.url, "_blank");
        } else {
          alert("Error: " + (response.data?.message || "Unknown error"));
        }
      },
    ).fail(function () {
      alert("Failed to generate preview URL");
    });
  }

  function renderPreview(data) {
    const chat = data.chat;
    const messages = data.messages || [];

    let html = `
            <div class="chatstory-container">
                <div class="chatstory-header">
                    <div class="chatstory-title">${escapeHtml(chat.title)}</div>
                    <div class="chatstory-description">${escapeHtml(chat.description || "")}</div>
                </div>
                <div class="chatstory-messages">
        `;

    if (messages.length === 0) {
      html +=
        '<div class="chatstory-no-messages">No messages in this chat yet.</div>';
    } else {
      messages.forEach(function (message, index) {
        const name = message.name || "Unknown";
        const role = message.role
          ? `<span class="chatstory-message-role">${escapeHtml(message.role)}</span>`
          : "";
        const timestamp = message.timestamp
          ? `<span class="chatstory-message-timestamp">${escapeHtml(message.timestamp)}</span>`
          : "";

        let avatarHtml;
        if (message.avatar) {
          avatarHtml = `<img src="${escapeHtml(message.avatar)}" alt="${escapeHtml(name)}" class="chatstory-avatar">`;
        } else {
          const initial = name.charAt(0).toUpperCase();
          avatarHtml = `<div class="chatstory-avatar-placeholder">${initial}</div>`;
        }

        const messageText = formatMessage(message.message);

        html += `
                    <div class="chatstory-message" style="animation-delay: ${index * 0.1}s">
                        <div class="chatstory-message-avatar">
                            ${avatarHtml}
                        </div>
                        <div class="chatstory-message-content">
                            <div class="chatstory-message-header">
                                <span class="chatstory-message-name">${escapeHtml(name)}</span>
                                ${role}
                                ${timestamp}
                            </div>
                            <div class="chatstory-message-bubble">
                                ${messageText}
                            </div>
                        </div>
                    </div>
                `;
      });
    }

    html += `
                </div>
            </div>
        `;

    $("#preview-container").html(html);
  }

  function formatMessage(text) {
    const paragraphs = text.split("\n\n");
    if (paragraphs.length > 1) {
      return paragraphs.map((p) => `<p>${escapeHtml(p.trim())}</p>`).join("");
    }
    return escapeHtml(text).replace(/\n/g, "<br>");
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
