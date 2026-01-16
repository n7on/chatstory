jQuery(document).ready(function ($) {
  $(".chatstory-container").each(function () {
    const container = $(this);
    const chatId = container.data("chat-id");

    if (!chatId) {
      showError(container, "No chat ID specified");
      return;
    }

    loadChat(container, chatId);
  });

  function loadChat(container, chatId) {
    $.ajax({
      url: ChatStoryAjax.rest_url + 'chats/' + chatId + '/frontend',
      method: 'GET',
      dataType: 'json',
      cache: false
    })
      .done(function (data) {
        if (data && data.chat) {
          initLivePlayback(container, data);
        } else {
          showError(container, data?.message || "Failed to load chat");
        }
      })
      .fail(function (xhr) {
        const message = xhr.responseJSON?.message || "Failed to load chat";
        showError(container, message);
      });
  }

  function initLivePlayback(container, data) {
    const chat = data.chat;
    const messages = data.messages || [];
    const reactions = data.reactions || [];
    const typing_events = data.typing_events || [];
    const presence_events = data.presence_events || [];

    console.log("Typing events:", typing_events);
    console.log("Presence events:", presence_events);

    // Update header
    container.find(".chatstory-title").text(chat.title);
    container.find(".chatstory-description").text(chat.description || "");

    // Clear messages container
    const messagesContainer = container.find(".chatstory-messages");
    messagesContainer.empty();

    if (messages.length === 0 && presence_events.length === 0) {
      messagesContainer.append(
        '<div class="chatstory-no-messages">No messages in this chat yet.</div>',
      );
      return;
    }

    // Store state
    container.data("messages", messages);
    container.data("reactions", reactions);
    container.data("typing_events", typing_events);
    container.data("presence_events", presence_events);
    container.data("isPlaying", false);
    container.data("speed", 1); // Fixed speed at 1x
    container.data("activeTimers", []);
    container.data("currentlyTyping", []);

    // Auto-start playback
    startPlayback(
      container,
      messagesContainer,
      messages,
      reactions,
      typing_events,
      presence_events,
    );
  }

  function startPlayback(
    container,
    messagesContainer,
    messages,
    reactions,
    typing_events,
    presence_events,
  ) {
    container.data("isPlaying", true);
    container.data("pausedAt", null);
    container.data("currentlyTyping", []);
    messagesContainer.empty();

    // Schedule messages, reactions, typing, and presence events
    scheduleAllMessages(container, messagesContainer, messages, 0);
    scheduleAllReactions(container, messagesContainer, reactions);
    scheduleAllTypingEvents(container, messagesContainer, typing_events);
    scheduleAllPresenceEvents(container, messagesContainer, presence_events);
  }

  function pausePlayback(container) {
    container.data("isPlaying", false);
    container.data("pausedAt", Date.now());

    // Clear all active timers
    const activeTimers = container.data("activeTimers") || [];
    activeTimers.forEach((timer) => clearTimeout(timer.id));

    // Store remaining times for each timer
    const pausedTimers = activeTimers.map((timer) => ({
      ...timer,
      remainingTime: timer.scheduledTime - Date.now(),
    }));
    container.data("pausedTimers", pausedTimers);
    container.data("activeTimers", []);
  }

  function resumePlayback(container, messagesContainer, messages) {
    container.data("isPlaying", true);

    const pausedTimers = container.data("pausedTimers") || [];
    const activeTimers = [];

    // Resume all paused timers with their remaining time
    pausedTimers.forEach((timer) => {
      const adjustedTime = Math.max(0, timer.remainingTime);

      const newTimerId = setTimeout(() => {
        timer.callback();
        // Remove this timer from active timers
        const timers = container.data("activeTimers") || [];
        container.data(
          "activeTimers",
          timers.filter((t) => t.id !== newTimerId),
        );
      }, adjustedTime);

      activeTimers.push({
        id: newTimerId,
        scheduledTime: Date.now() + adjustedTime,
        callback: timer.callback,
        type: timer.type,
      });
    });

    container.data("activeTimers", activeTimers);
    container.data("pausedTimers", []);
  }

  function scheduleAllMessages(
    container,
    messagesContainer,
    messages,
    startTime,
  ) {
    const speed = container.data("speed");

    console.log("Scheduling messages:", messages);

    messages.forEach((message, index) => {
      // start_time is when the message should appear (in seconds from conversation start)
      const startTimeSeconds = parseFloat(message.start_time) || 0;
      const messageAppearTime = (startTimeSeconds * 1000) / speed;

      console.log(
        `Message ${index}: "${message.message.substring(0, 30)}..." - start_time: ${startTimeSeconds}s, appears at: ${messageAppearTime}ms`,
      );

      // Schedule message appearance
      scheduleTimer(
        container,
        messageAppearTime,
        () => {
          if (!container.data("isPlaying")) return;
          displayMessage(messagesContainer, message, index);

          // Last message - no action needed since we removed the button
        },
        "message",
      );
    });
  }

  function scheduleAllReactions(container, messagesContainer, reactions) {
    const speed = container.data("speed");

    reactions.forEach((reaction) => {
      const startTimeSeconds = parseFloat(reaction.start_time) || 0;
      const reactionAppearTime = (startTimeSeconds * 1000) / speed;

      scheduleTimer(
        container,
        reactionAppearTime,
        () => {
          if (!container.data("isPlaying")) return;
          displayReaction(messagesContainer, reaction);
        },
        "reaction",
      );
    });
  }

  function scheduleAllTypingEvents(
    container,
    messagesContainer,
    typing_events,
  ) {
    const speed = container.data("speed");

    typing_events.forEach((typing) => {
      const startTimeSeconds = parseFloat(typing.start_time) || 0;
      const duration = parseFloat(typing.duration) || 3;
      const typingStartTime = (startTimeSeconds * 1000) / speed;
      const typingEndTime = ((startTimeSeconds + duration) * 1000) / speed;

      // Schedule typing start
      scheduleTimer(
        container,
        typingStartTime,
        () => {
          if (!container.data("isPlaying")) return;
          addTypingPerson(container, messagesContainer, typing);
        },
        "typing-start",
      );

      // Schedule typing end
      scheduleTimer(
        container,
        typingEndTime,
        () => {
          if (!container.data("isPlaying")) return;
          removeTypingPerson(container, messagesContainer, typing);
        },
        "typing-end",
      );
    });
  }

  function scheduleAllPresenceEvents(
    container,
    messagesContainer,
    presence_events,
  ) {
    const speed = container.data("speed");

    presence_events.forEach((presence) => {
      const startTimeSeconds = parseFloat(presence.start_time) || 0;
      const delay = (startTimeSeconds * 1000) / speed;

      scheduleTimer(
        container,
        delay,
        () => {
          if (!container.data("isPlaying")) return;
          showPresenceNotification(messagesContainer, presence);
        },
        "presence",
      );
    });
  }

  function showPresenceNotification(messagesContainer, presence) {
    const actionText =
      presence.action === "join" ? "joined the chat" : "left the chat";
    const actionClass =
      presence.action === "join"
        ? "chatstory-presence-join"
        : "chatstory-presence-leave";

    const notification = $(`
      <div class="chatstory-presence ${actionClass}">
        <span class="chatstory-presence-text">
          <strong>${presence.name}</strong> ${actionText}
        </span>
      </div>
    `);

    messagesContainer.append(notification);
    scrollToBottom(messagesContainer);
  }

  function scheduleTimer(container, delay, callback, type) {
    const scheduledTime = Date.now() + delay;

    const timerId = setTimeout(() => {
      callback();
      // Remove this timer from active timers
      const timers = container.data("activeTimers") || [];
      container.data(
        "activeTimers",
        timers.filter((t) => t.id !== timerId),
      );
    }, delay);

    const activeTimers = container.data("activeTimers") || [];
    activeTimers.push({
      id: timerId,
      scheduledTime: scheduledTime,
      callback: callback,
      type: type,
    });
    container.data("activeTimers", activeTimers);
  }

  function addTypingPerson(container, messagesContainer, typing) {
    const currentlyTyping = container.data("currentlyTyping") || [];

    // Add this person to typing list if not already there
    if (
      !currentlyTyping.find((p) => p.character_id === typing.character_id)
    ) {
      currentlyTyping.push({
        character_id: typing.character_id,
        name: typing.name,
      });
      container.data("currentlyTyping", currentlyTyping);
      updateTypingIndicator(messagesContainer, currentlyTyping);
    }
  }

  function removeTypingPerson(container, messagesContainer, typing) {
    let currentlyTyping = container.data("currentlyTyping") || [];

    // Remove this person from typing list
    currentlyTyping = currentlyTyping.filter(
      (p) => p.character_id !== typing.character_id,
    );
    container.data("currentlyTyping", currentlyTyping);
    updateTypingIndicator(messagesContainer, currentlyTyping);
  }

  function updateTypingIndicator(messagesContainer, typingPeople) {
    // Remove existing indicator
    messagesContainer.find(".chatstory-typing-indicator").remove();

    if (typingPeople.length === 0) {
      return;
    }

    // Build names list: "John", "John, Sarah", "John, Sarah, Mike"
    const names = typingPeople.map((p) => escapeHtml(p.name)).join(", ");

    const indicator = $(`
      <div class="chatstory-typing-indicator">
        <div class="chatstory-typing-dots">
          <div class="chatstory-typing-dot"></div>
          <div class="chatstory-typing-dot"></div>
          <div class="chatstory-typing-dot"></div>
        </div>
        <div class="chatstory-typing-text">${names} is typing...</div>
      </div>
    `);

    messagesContainer.append(indicator);
    scrollToBottom(messagesContainer);
  }

  function displayMessage(messagesContainer, message, index) {
    const messageHtml = createMessageElement(message, index);
    messagesContainer.append(messageHtml);
    scrollToBottom(messagesContainer);
  }

  function displayReaction(messagesContainer, reaction) {
    // Find the message element
    const messageElement = messagesContainer.find(
      `.chatstory-message[data-message-id="${reaction.target_event_id}"]`,
    );

    if (!messageElement.length) {
      console.warn("Message not found for reaction:", reaction);
      return;
    }

    // Check if reactions container exists, if not create it
    let reactionsContainer = messageElement.find(
      ".chatstory-message-reactions",
    );
    if (!reactionsContainer.length) {
      reactionsContainer = $('<div class="chatstory-message-reactions"></div>');
      messageElement
        .find(".chatstory-message-bubble")
        .after(reactionsContainer);
    }

    // Add the reaction with animation
    const reactionBadge = $(
      `<span class="chatstory-reaction" title="${escapeHtml(reaction.name)}">${escapeHtml(reaction.reaction)}</span>`,
    );
    reactionsContainer.append(reactionBadge);

    // Animate the reaction
    reactionBadge.hide().fadeIn(200);
  }

  function createMessageElement(message, index) {
    const name = message.name || "Unknown";
    const role = message.role
      ? `<span class="chatstory-message-role">${escapeHtml(
          message.role,
        )}</span>`
      : "";
    const timestamp = message.timestamp
      ? `<span class="chatstory-message-timestamp">${escapeHtml(
          message.timestamp,
        )}</span>`
      : "";

    let avatarHtml;
    if (message.avatar) {
      avatarHtml = `<img src="${escapeHtml(
        message.avatar,
      )}" alt="${escapeHtml(name)}" class="chatstory-avatar">`;
    } else {
      const initial = name.charAt(0).toUpperCase();
      avatarHtml = `<div class="chatstory-avatar-placeholder">${initial}</div>`;
    }

    const messageText = formatMessage(message.message);

    return `
            <div class="chatstory-message" data-message-id="${message.id}">
                <div class="chatstory-message-avatar">
                    ${avatarHtml}
                </div>
                <div class="chatstory-message-content">
                    <div class="chatstory-message-header">
                        <span class="chatstory-message-name">${escapeHtml(
                          name,
                        )}</span>
                        ${role}
                        ${timestamp}
                    </div>
                    <div class="chatstory-message-bubble">
                        ${messageText}
                    </div>
                </div>
            </div>
        `;
  }

  function formatMessage(text) {
    const paragraphs = text.split("\n\n");
    if (paragraphs.length > 1) {
      return paragraphs.map((p) => `<p>${escapeHtml(p.trim())}</p>`).join("");
    }
    return escapeHtml(text).replace(/\n/g, "<br>");
  }

  function scrollToBottom(container) {
    container.animate(
      {
        scrollTop: container[0].scrollHeight,
      },
      300,
    );
  }

  function showError(container, message) {
    container
      .find(".chatstory-messages")
      .html(`<div class="chatstory-error">${escapeHtml(message)}</div>`);
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
