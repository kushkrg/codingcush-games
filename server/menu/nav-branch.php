                <?php elseif (!empty($mi['children']) && stripos((string) $mi['label'], 'tool') !== false): ?>
                <!-- Free Tools : mega menu. Tool cards come live from Admin -> Tools;
                     the typing test is featured on the left. -->
                <?php
                    $ccTools = [];
                    try { $ccTools = \App\Models\Tool::active(); } catch (\Throwable $e) { $ccTools = []; }
                ?>
                <div class="cc-nav__item cc-has-mega">
                    <button class="cc-nav__link" type="button" aria-haspopup="true"><?= h($mi['label']) ?>
                        <svg class="cc-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <div class="cc-mega cc-mega--tools" role="menu">
                        <div class="cc-mega__grid">
                            <a class="cc-mega__course cc-tt-card" href="/typing-test/">
                                <span class="cc-mono">NEW · TYPING TEST</span>
                                <h4>How fast can you type?</h4>
                                <p>Check your WPM and accuracy in 30 seconds. Four levels, from simple words to real code.</p>
                                <span class="cc-tt-demo" aria-hidden="true">the quick brown<i></i><em> fox jumps</em></span>
                                <span class="cc-tt-tags"><span>4 levels</span><span>Speed graph</span><span>Missed keys</span><span>Share card</span></span>
                                <span class="cc-tt-go">Take the typing test <span aria-hidden="true">→</span></span>
                            </a>
                            <div>
                                <p class="cc-mega__title">Developer &amp; text tools</p>
                                <div class="cc-tools-grid">
                                <?php if (!empty($ccTools)): ?>
                                    <?php foreach ($ccTools as $ccTool): ?>
                                        <a class="cc-mlink" href="/tools/<?= h($ccTool['slug']) ?>/">
                                            <span class="cc-mega__ic"><?= h($ccTool['icon_text'] ?? '') ?></span>
                                            <span><b><?= h($ccTool['name']) ?></b><span class="cc-mlink-sub"><?= h($ccTool['short_description'] ?? '') ?></span></span>
                                        </a>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <?php foreach ($mi['children'] as $ch): ?>
                                        <a class="cc-mlink" href="<?= h(\App\Models\Menu::resolveUrl($ch)) ?>"><span><b><?= h($ch['label']) ?></b></span></a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="cc-mega__foot">
                            <span>Free forever · no sign-up · everything runs in your browser</span>
                            <span>·</span>
                            <a href="/tools/">Browse all free tools</a>
                        </div>
                    </div>
                </div>
